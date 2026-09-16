# Upgrade Guide

## From genesis/behat-fail-aid to robertfausk/behat-fail-aid (next major)

### Package rename

The Packagist package has been renamed from `genesis/behat-fail-aid` to `robertfausk/behat-fail-aid`.

Update your `composer.json`:

```diff
-    "genesis/behat-fail-aid": "^<old-version>",
+    "robertfausk/behat-fail-aid": "^<new-version>",
```

Then run:

```shell
composer remove genesis/behat-fail-aid
composer require robertfausk/behat-fail-aid --dev
```

---

### PHP requirement raised to >=8.3

PHP 8.2 is no longer supported (security support ends December 2026).
Ensure your environment runs PHP 8.3 or higher.

---

### behat/mink-extension replaced by friends-of-behat/mink-extension

`behat/mink-extension` has been replaced with `friends-of-behat/mink-extension ^2.7`.
The package uses the same namespace (`Behat\MinkExtension`) and is a drop-in replacement —
no changes to your `behat.yml` or context files are required.

If you have `behat/mink-extension` pinned in your own `composer.json`, remove it:

```diff
-    "behat/mink-extension": "^2.3",
+    "friends-of-behat/mink-extension": "^2.7",
```

---

### Container parameter keys renamed

If you reference behat-fail-aid container parameters directly (e.g. in custom extensions
or service definitions), update the prefix:

| Old key | New key |
|---|---|
| `genesis.failaid.config.screenshot` | `failaid.config.screenshot` |
| `genesis.failaid.config.output` | `failaid.config.output` |
| `genesis.failaid.config.debugBarSelectors` | `failaid.config.debugBarSelectors` |
| `genesis.failaid.config.siteFilters` | `failaid.config.siteFilters` |
| `genesis.failaid.config.trackJs` | `failaid.config.trackJs` |
| `genesis.failaid.config.defaultSession` | `failaid.config.defaultSession` |

Most users do not reference these parameters directly and are not affected.

---

### Deprecated screenshot options removed

The top-level `screenshotDirectory` and `screenshotMode` configuration keys have been
deprecated since the introduction of the `screenshot` block and are now removed.

```diff
 # behat.yml
 FailAid\Extension:
-    screenshotDirectory: /tmp/screenshots
-    screenshotMode: html
+    screenshot:
+        directory: /tmp/screenshots
+        mode: html
```

---

### ReflectionProperty::setAccessible / ReflectionMethod::setAccessible removed

Internal calls to `setAccessible()` have been removed — these were no-ops since PHP 8.1.
No action required.
