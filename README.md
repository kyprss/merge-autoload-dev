# Merge Autoload Dev

A Composer plugin that automatically merges autoload-dev configurations from local packages into the root composer.json.

## Why?

When working on a monolithic project structured into modules via local Composer packages, the `autoload-dev` settings from sub-packages are not loaded by default.
As a result, test helpers inside a module are not automatically available.
This plugin merges those configurations so that your test helpers become accessible when running the project's tests.

## Installation

```bash
composer require kyprss/merge-autoload-dev --dev
```

## Configuration

Add the following to your root `composer.json`:

```json
{
    "extra": {
        "merge-autoload-dev": {
            "include": [
                "modules/*/composer.json"
            ]
        }
    }
}
```

### Configuration Options

- **include** (array): Glob patterns for composer.json files to scan and merge

## How It Works

1. The plugin scans all files matching the `include` patterns
2. For each `composer.json` file found, it reads the `autoload-dev` configuration
3. It merges all found autoload-dev configurations into the root project's autoload-dev
4. The merged configuration is automatically applied when running composer commands

## Example

Given the following structure:

```
project/
├── composer.json
├── modules/
│   ├── module-a/
│   │   └── composer.json (with autoload-dev)
│   └── module-b/
│       └── composer.json (with autoload-dev)
```
