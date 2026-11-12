# Changelog

## 0.11.1 – 2026-11-12

### Fixed

- Requirements on other DenoSys packages are `^0.11`. In 0.11.0 they were
  still `^0.10`, which for a 0.x version allows only 0.10 releases, so 0.11.0
  packages resolved their siblings to 0.10 or could not be installed. Use
  0.11.1 instead of 0.11.0.

## 0.11.0 – 2026-11-12

- **Breaking:** `Denosys\View\ViewServiceProvider` moved to the framework as `Denosys\Framework\Providers\ViewServiceProvider`. Packages no longer ship service providers; the framework wires packages together.
- `ViewEngine` implements `TemplateRenderer`.

## 0.10.0 – 2026-11-12

### Changed

- Released from the [denosyscore/framework](https://github.com/denosyscore/framework)
  monorepo, where this package now lives under `packages/view`. All DenoSys
  packages share one version line, so the version moves from v0.3.0 to
  0.10.0. There are no API changes in this release.
- Requirements on other DenoSys packages are `^0.10`.

## 0.3.0 – 2026-11-11

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
  system temp directory, created with mode 0700. It is refused if it is a
  symlink, owned by another user, or writable by other users, because a
  predictable temp path could otherwise be pre-created by another local user
  to plant compiled templates. Configure an application-owned cache directory
  in production.
- Compiled templates are written to a temporary file and renamed, so
  concurrent requests never include a partially written file.

### Testing

- Add a PHPUnit suite and run it in CI.
