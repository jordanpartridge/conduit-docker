# Conduit docker

A Conduit component for docker functionality

## Installation

```bash
# Via Conduit component system
conduit components install docker

# Via Composer (if published)
composer require jordanpartridge/conduit-docker
```

## Commands

- `conduit docker:init`

## Usage

```bash
# Example usage
conduit docker:init --help
```

## Development

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Code formatting
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyze
```

## License

MIT License. See [LICENSE](LICENSE) for details.