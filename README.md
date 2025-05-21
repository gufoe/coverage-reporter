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

// Generate HTML report
Coverage::generateHtmlReport('/path/to/output/folder', $coverageData);

// To speed up the report generation, you can filter the files to be included in the report.
Coverage::generateHtmlReport('/path/to/output/folder', $coverageData, [
    '/path/to/include/directory/',
    '/path/to/include/file.php',
]);
```

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
