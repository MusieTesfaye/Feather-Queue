# Contributing to FeatherQueue

First of all, thank you for your interest in contributing to FeatherQueue! We value your time and effort in making this project better and more secure. This document outlines the guidelines for contributing, ensuring code quality, and maintaining security best practices.

## Table of Contents
- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How to Contribute](#how-to-contribute)
  - [Reporting Issues](#reporting-issues)
  - [Submitting Code Changes](#submitting-code-changes)
  - [Security Considerations](#security-considerations)
  - [Coding Standards](#coding-standards)
- [Testing and Validation](#testing-and-validation)
- [Review Process](#review-process)
- [License](#license)

---

## Code of Conduct
We follow the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). Please ensure that your interactions remain respectful and inclusive.

## Getting Started
To start contributing:
1. Fork the repository on GitHub.
2. Clone your forked repository:
   ```sh
   git clone https://github.com/musietesfaye/Feather-Queue.git
   cd Feather-Queue
   ```
3. Set up your development environment:
   ```sh
   composer install
   ```
4. Create a new branch for your feature or fix:
   ```sh
   git checkout -b feature-name
   ```

## How to Contribute

### Reporting Issues
If you encounter any issues or have suggestions for improvements:
- **Check existing issues**: Ensure your issue is not already reported.
- **Provide details**: Include steps to reproduce the issue, expected vs. actual behavior, and relevant logs if applicable.
- **Use appropriate labels**: Security, bug, enhancement, etc.

### Submitting Code Changes
1. Follow the [Getting Started](#getting-started) steps.
2. Ensure your changes follow our [Coding Standards](#coding-standards).
3. Commit with clear and descriptive messages:
   ```sh
   git commit -m "Fix issue #123: Detailed description of fix"
   ```
4. Push your changes to your fork:
   ```sh
   git push origin feature-name
   ```
5. Open a Pull Request (PR) to the `main` branch with a clear title and description.

### Security Considerations
Security is a top priority. Follow these best practices:
- **No hardcoded credentials**: Avoid storing API keys, passwords, or sensitive data in the codebase.
- **Sanitize inputs**: Always validate and sanitize user inputs to prevent injection attacks.
- **Use safe file handling**: Avoid arbitrary file execution or manipulation.
- **Report vulnerabilities responsibly**: If you find a security issue, please report it to `security@featherqueue.com` instead of posting it publicly.

### Coding Standards
- Follow **PSR-4 Autoloading** and **PSR-12 Coding Standards**.
- Use meaningful variable and function names.
- Write clear, concise comments where necessary.
- Use proper indentation (spaces over tabs).
- Run `phpcs` before committing:
  ```sh
  composer run phpcs
  ```

## Testing and Validation
Before submitting changes:
- Run unit tests:
  ```sh
  composer test
  ```
- Ensure code formatting compliance:
  ```sh
  composer run lint
  ```
- Validate security best practices.

## Review Process
All contributions go through a review process:
- A maintainer will review your PR within a reasonable time.
- You may be asked for modifications before approval.
- Approved changes will be merged into `main`.

## License
By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE.md).

Thank you for contributing to FeatherQueue!

