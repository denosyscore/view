# Changelog

## Unreleased

### Changed

- `ViewEngine` renders `.php` and `.tpl` templates with `TemplateEngine`,
  which compiles directives and escapes `{{ }}` output. It previously used
  `SimpleTemplateEngine`, so directives were printed raw, nothing was escaped,
  and directives registered with `ViewEngine::directive()` (including the
  provider's `money`, `asset`, `url` and `route`) were silently dropped.
- `ViewEngine::exists()` reports existing templates; it previously always
  returned false.
- `.phtml` templates are found by name and rendered with `PhpEngine`.

### Fixed

- `TemplateEngine` always compiles templates. Without a cache directory it
  previously included the uncompiled source, printing directives and
  unescaped output. The default cache directory is a per-user folder in the
  system temp directory.
- Compiled templates are written to a temporary file and renamed, so
  concurrent requests never include a partially written file.

### Testing

- Add a PHPUnit suite and run it in CI.
