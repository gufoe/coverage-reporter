# Coverage Reporter

A minimal, type-safe PHP library for generating beautiful HTML code coverage reports using Xdebug. The codebase is unified, DRY, and easy to maintain, with a single data model and template system.

## Features

- **Easy HTML Coverage Reports**: Instantly generate beautiful, interactive HTML reports to visualize your code coverage.
- **Simple Integration**: Just a few lines of code to start and collect coverage—no complex setup required.
- **Clear Directory & File Views**: Navigate your project's coverage with intuitive directory and file breakdowns.
- **Actionable Insights**: Quickly spot untested lines and files, helping you improve your test suite efficiently.
- **Modern, Fast, and Lightweight**: Built for speed and clarity, with no unnecessary bloat or confusing options.
- **One-Command Report Generation**: Create reports with a single function call—no manual steps or extra tools needed.
- **Works Out-of-the-Box**: No configuration required for standard PHP projects; just install and use.
- **Shareable Reports**: Output is static HTML, so you can easily share coverage results with your team or include them in CI pipelines.

## Requirements

- PHP 8.1 or higher
- Xdebug extension with coverage enabled
- Composer

## Installation

```bash
composer require gufoe/coverage-reporter
```

## Usage

Execute the following code in your project with `php -d xdebug.mode=coverage` to collect coverage data and generate a report.

If using php-fpm, you will have to set the `xdebug.mode` in the configuration file (e.g. `php-fpm.conf`).

```php
use CoverageReporter\Coverage;

// Start collecting coverage data
Coverage::start();

// Your code to be analyzed goes here
// ...

// Stop collecting and get the coverage data
$coverageData = Coverage::stop();

// Create a builder for your project root directory
$builder = Coverage::builder('/path/to/project/root');

// Include files or directories you want to analyze
$builder->includeFile('/path/to/file.php');
$builder->includeFile('/path/to/another/file.php');
// Or include all files in a directory
$builder->includeAll();

// Add the coverage data to the builder
$builder->addCoverageData($coverageData);

// Generate HTML report
$builder->buildHtmlReport('/path/to/output/folder');

// You can also generate a JSON report
$jsonReport = $builder->buildJsonReport();
```

The builder supports method chaining for a more fluent interface:

```php
// Create a builder and configure it in one go
$builder = Coverage::builder('/path/to/project/root')
    ->includeFile('/path/to/file.php')
    ->includeFile('/path/to/another/file.php')
    ->addCoverageData($coverageData);

// Generate the report
$builder->buildHtmlReport('/path/to/output/folder');
```

## Merging Multiple Coverage Runs

You can handle multiple coverage runs in two ways:

### Method 1: Merge Coverage Data First

This approach combines the coverage data before creating the report:

```php
use CoverageReporter\Coverage;

// Run 1
Coverage::start();
// ... code or tests for the first run ...
$coverage1 = Coverage::stop();

// Run 2
Coverage::start();
// ... code or tests for the second run ...
$coverage2 = Coverage::stop();

// Merge the coverage arrays
$mergedCoverage = Coverage::mergeCoverage([$coverage1, $coverage2]);

// Create a builder and generate a report with the merged data
$builder = Coverage::builder('/path/to/project/root')
    ->includeAll()
    ->addCoverageData($mergedCoverage)
    ->buildHtmlReport('/path/to/output/folder');
```

### Method 2: Add Coverage Data Directly

Alternatively, you can add each coverage data set directly to the builder:

```php
use CoverageReporter\Coverage;

// Run 1
Coverage::start();
// ... code or tests for the first run ...
$coverage1 = Coverage::stop();

// Run 2
Coverage::start();
// ... code or tests for the second run ...
$coverage2 = Coverage::stop();

// Create a builder and add each coverage data set
$builder = Coverage::builder('/path/to/project/root')
    ->includeAll()
    ->addCoverageData($coverage1)
    ->addCoverageData($coverage2)
    ->buildHtmlReport('/path/to/output/folder');
```

Both methods will produce the same result. Choose the one that better fits your workflow. The first method is useful if you need to manipulate the merged data before generating the report, while the second method is simpler if you just want to combine the coverage data directly.

## Architecture

- **Unified Coverage Model**: Files and directories are represented in a single, consistent structure for easy navigation.
- **Central Report Builder**: Generates both HTML and JSON reports from your coverage data.
- **Modern Templates**: Clean, minimal templates ensure fast and attractive report generation.

## Development

### Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```

### Running Tests

```bash
composer test
```

### Code Quality

The project uses PHPStan for static analysis:

```bash
# Run static analysis
composer phpstan

```

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Security

If you discover any security related issues, please email your.email@example.com instead of using the issue tracker. 
