<?php

namespace CoverageReporter;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Expr;
use PhpParser\NodeVisitorAbstract;

class SyntheticCoverageGenerator
{
    /** @var string The source code of the file */
    private string $code;

    /** @var array<int,int> Coverage data: line => -1 */
    private array $coverageData = [];

    /** @var array<string,array{start:int,end:int}> Map of method/function scopes */
    private array $methodScopes = [];

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    /**
     * Generate synthetic coverage: mark all Xdebug-executable lines as -1
     * @return array<int,int>
     */
    public function generate(): array
    {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        try {
            $ast = $parser->parse($this->code);
            if (!$ast) {
                return [];
            }

            // resolve names and link parents
            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NameResolver());
            $traverser->addVisitor(new ParentConnector());
            $ast = $traverser->traverse($ast);

            foreach ($ast as $node) {
                $this->markExecutableLines($node);
            }

            return $this->coverageData;
        } catch (\PhpParser\Error $e) {
            return [];
        }
    }

    private function markExecutableLines(Node $node): void
    {
        $attrs = $node->getAttributes();
        if (!isset($attrs['startLine'])) {
            return;
        }
        $start = $attrs['startLine'];

        // track method/function scopes
        if ($node instanceof Stmt\ClassMethod || $node instanceof Stmt\Function_) {
            $name = $node instanceof Stmt\ClassMethod
                ? $node->getAttribute('parent')->name->toString() . '::' . $node->name->toString()
                : $node->name->toString();
            $this->methodScopes[$name] = ['start' => $start, 'end' => $node->getEndLine()];
        }

        // handle class constants and top-level consts (executed on include)
        if ($node instanceof Stmt\Const_ || $node instanceof Stmt\ClassConst) {
            $this->coverageData[$start] = -1;
        }

        // handle property initializers only
        if ($node instanceof Stmt\Property) {
            foreach ($node->props as $prop) {
                if ($prop->default instanceof Expr) {
                    $this->coverageData[$prop->default->getStartLine()] = -1;
                }
            }
        }

        // control structures: only mark condition expression lines
        $map = [
            Stmt\If_::class      => fn($n) => $n->cond,
            Stmt\ElseIf_::class  => fn($n) => $n->cond,
            Stmt\Else_::class    => fn($n) => $n,
            Stmt\For_::class     => fn($n) => $n,
            Stmt\Foreach_::class => fn($n) => $n->expr,
            Stmt\While_::class   => fn($n) => $n->cond,
            Stmt\Do_::class      => fn($n) => $n->cond,
            Stmt\Switch_::class  => fn($n) => $n->cond,
            Stmt\Case_::class    => fn($n) => ($n->cond ?? $n),
            Stmt\Catch_::class   => fn($n) => $n,
            Stmt\Finally_::class => fn($n) => $n,
            Stmt\Break_::class   => fn($n) => $n,
            Stmt\Continue_::class => fn($n) => $n,
            Stmt\Goto_::class    => fn($n) => $n,
            Stmt\Label::class    => fn($n) => $n,
        ];

        foreach ($map as $class => $resolver) {
            if ($node instanceof $class) {
                $target = $resolver($node);
                if ($target instanceof Node) {
                    $this->coverageData[$target->getStartLine()] = -1;
                } else {
                    $this->coverageData[$start] = -1;
                }
                break;
            }
        }

        // executable expressions and statements (excluding declarations)
        if ($this->isExecutableNode($node)) {
            $this->coverageData[$start] = -1;
        }

        // recurse
        foreach ($node->getSubNodeNames() as $sub) {
            $child = $node->$sub;
            if ($child instanceof Node) {
                $this->markExecutableLines($child);
            } elseif (is_array($child)) {
                foreach ($child as $c) {
                    if ($c instanceof Node) {
                        $this->markExecutableLines($c);
                    }
                }
            }
        }
    }

    private function isExecutableNode(Node $node): bool
    {
        // skip class, interface, trait, function declarations
        if ($node instanceof Stmt\Class_ || $node instanceof Stmt\Interface_ || $node instanceof Stmt\Trait_ || $node instanceof Stmt\Function_) {
            return false;
        }
        // top-level expressions
        if ($node instanceof Stmt\Expression) {
            return true;
        }
        // return statements: only if method invoked (dynamic merge)
        if ($node instanceof Stmt\Return_) {
            $scope = $this->findContainingMethod($node);
            return !$scope || $this->isMethodExecuted($scope);
        }
        // general expressions (calls, assigns, etc.)
        if ($node instanceof Expr) {
            return true;
        }
        return false;
    }

    private function findContainingMethod(Node $node): ?array
    {
        $line = $node->getStartLine();
        foreach ($this->methodScopes as $scope) {
            if ($line >= $scope['start'] && $line <= $scope['end']) {
                return $scope;
            }
        }
        return null;
    }

    private function isMethodExecuted(array $scope): bool
    {
        foreach (range($scope['start'], $scope['end']) as $ln) {
            if (isset($this->coverageData[$ln]) && $this->coverageData[$ln] > 0) {
                return true;
            }
        }
        return false;
    }
}

class ParentConnector extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        foreach ($node->getSubNodeNames() as $name) {
            $child = $node->$name;
            if ($child instanceof Node) {
                $child->setAttribute('parent', $node);
            } elseif (is_array($child)) {
                foreach ($child as $c) {
                    if ($c instanceof Node) {
                        $c->setAttribute('parent', $node);
                    }
                }
            }
        }
    }
}
