# Integrity Tests - Command Cheat Sheet

## Quick Start

### Most Common Commands

```bash
# Run all integrity tests (recommended)
cd tests/unit && ../../vendor/bin/phpunit --testsuite Integrity --testdox

# Run from project root
./vendor/bin/phpunit -c tests/unit/phpunit.xml.dist --testsuite Integrity --testdox

# Quick check (no formatting)
cd tests/unit && ../../vendor/bin/phpunit --testsuite Integrity
```

## All Commands

### 1) Run All Integrity Tests

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity
```

**Expected output:**
```
Tests: 17, Assertions: 19, Skipped: 2
Time: ~0.04 seconds
```

### 2) Run with Pretty Output (TestDox)

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --testdox
```

**Shows:**
- Test passed
- Test failed
- Test skipped

### 3) Run Specific Test Class

```bash
cd tests/unit

# Config Structure Tests (4 tests)
../../vendor/bin/phpunit ../../src/Test/Integrity/Testsuite/ConfigStructureTest.php

# Patch File Naming Tests (2 tests)
../../vendor/bin/phpunit ../../src/Test/Integrity/Testsuite/PatchFileNamingTest.php

# Patches Directory Tests (4 tests)
../../vendor/bin/phpunit ../../src/Test/Integrity/Testsuite/PatchesDirectoryTest.php

# Supported Versions Tests (7 tests)
../../vendor/bin/phpunit ../../src/Test/Integrity/Testsuite/SupportedVersionsTest.php
```

### 4) Run Specific Test Method

```bash
cd tests/unit

# Test patches.json structure
../../vendor/bin/phpunit --filter testPatchesJsonStructure

# Test file existence
../../vendor/bin/phpunit --filter testAllReferencedPatchFilesExist

# Test version constraints
../../vendor/bin/phpunit --filter testVersionConstraintsAreValid

# Test naming conventions
../../vendor/bin/phpunit --filter testPatchFileNamingConventions
```

### 5) List All Available Tests

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --list-tests
```

**Output:** List of all 17 tests

### 6) Run with Colors

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --colors=always
```

### 7) Debug Mode (Verbose)

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --debug
```

**Shows:** Detailed execution information

### 8) Stop on First Failure

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --stop-on-failure
```

**Useful for:** Fixing errors one at a time

### 9) Run All Tests (Unit + Integrity)

```bash
cd tests/unit
../../vendor/bin/phpunit
```

**Runs:** Both Unit and Integrity test suites

### 10) Generate Coverage Report

```bash
cd tests/unit
../../vendor/bin/phpunit --testsuite Integrity --coverage-html ../../var/coverage
```

**View report:**
```bash
open ../../var/coverage/index.html
```

## Test Results Explained

### Green Dot (.)
Test passed successfully

### Red F
Test failed

### Yellow S
Test skipped (usually a warning, not a failure)

### D
Deprecation notice (can be ignored for now)

## Common Use Cases

### Before Committing Changes

```bash
# Quick validation
cd tests/unit && ../../vendor/bin/phpunit --testsuite Integrity
```

### After Editing patches.json

```bash
# Validate JSON structure
cd tests/unit && ../../vendor/bin/phpunit --filter ConfigStructureTest
```

### After Adding New Patch File

```bash
# Check file exists and naming is correct
cd tests/unit && ../../vendor/bin/phpunit --filter testAllReferencedPatchFilesExist
cd tests/unit && ../../vendor/bin/phpunit --filter testPatchFileNamingConventions
```

### After Changing Version Constraints

```bash
# Validate semver constraints
cd tests/unit && ../../vendor/bin/phpunit --filter testVersionConstraintsAreValid
```

## One-Liner Commands (Copy & Paste)

```bash
# From project root - all tests with pretty output
./vendor/bin/phpunit -c tests/unit/phpunit.xml.dist --testsuite Integrity --testdox

# From project root - quick check
./vendor/bin/phpunit -c tests/unit/phpunit.xml.dist --testsuite Integrity

# From project root - specific test
./vendor/bin/phpunit -c tests/unit/phpunit.xml.dist --filter testPatchesJsonStructure

# From tests/unit directory - most common
cd tests/unit && ../../vendor/bin/phpunit --testsuite Integrity --testdox
```

## Troubleshooting

### Error: "Could not find file"
**Solution:** Make sure you're in the correct directory
```bash
cd /opt/homebrew/var/www/magento-cloud-patches/tests/unit
```

### Error: "No tests executed"
**Solution:** Check the testsuite name (case-sensitive)
```bash
../../vendor/bin/phpunit --testsuite Integrity  # Correct
../../vendor/bin/phpunit --testsuite integrity  # Wrong
```

### Error: "Class not found"
**Solution:** Run composer install
```bash
cd /opt/homebrew/var/www/magento-cloud-patches
composer install
```

## Requirements

- PHP 8.1 or higher
- Composer dependencies installed
- No Docker required
- No Magento installation required

## Performance

**Integrity Tests:**
- 17 tests total
- ~0.04 seconds execution time
- Minimal memory usage (~8 MB)


## Quick Reference Table

| Command | Purpose | Speed |
|---------|---------|-------|
| `--testsuite Integrity` | All integrity tests | Fast |
| `--testdox` | Pretty output | Fast |
| `--filter <name>` | Specific test | Very fast |
| `--list-tests` | Show available tests | Very fast |
| `--debug` | Verbose output | Fast |
| `--stop-on-failure` | Stop on first error | Fast |
| `--coverage-html` | Coverage report | Slow |

## What Each Test Validates

### ConfigStructureTest (4 tests)
- patches.json is valid JSON
- JSON has correct structure
- All referenced files exist
- Version constraints are valid semver

### PatchFileNamingTest (2 tests)
- Files follow naming conventions
- No duplicate references (warning only)

### PatchesDirectoryTest (4 tests)
- patches/ directory exists
- Directory contains patch files
- All files are referenced
- File quality checks (warning only)

### SupportedVersionsTest (7 tests)
- Covers Magento 2.4.4+
- Covers PHP 8.1+ versions
- Major versions: 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8

---

**Pro Tip:** Add this to your bash/zsh aliases for quick access:

```bash
# Add to ~/.bashrc or ~/.zshrc
alias integrity='cd tests/unit && ../../vendor/bin/phpunit --testsuite Integrity --testdox'

# Usage:
cd /opt/homebrew/var/www/magento-cloud-patches
integrity
```
