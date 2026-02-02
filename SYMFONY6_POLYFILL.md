# Symfony 6.x compatibility polyfill

This document explains the origin and purpose of the `NamespacedAttributeBag` class and the modified `SessionFlowsBag` in this package.

## Problem

`SessionFlowsBag` originally extended Symfony’s `Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag`. That class was **deprecated in Symfony 5.3** and **removed in Symfony 6.x**. As a result, the bundle fails on Symfony 6+ with a `ClassNotFoundError` when trying to load `NamespacedAttributeBag` from the Symfony namespace.

## Solution

Instead of depending on the removed Symfony class, this package now ships its own implementation:

1. **`Storage/NamespacedAttributeBag.php`** – A polyfill that reimplements the behaviour of the former Symfony class. It extends `Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag` (which still exists in Symfony 6) and provides the same structured, namespaced session storage (e.g. keys like `domain/step` stored as nested arrays). The logic is based on the original Symfony 5.4 implementation.

2. **`Storage/SessionFlowsBag.php`** – Updated to extend `Sylius\Bundle\FlowBundle\Storage\NamespacedAttributeBag` instead of `Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag`, so it uses the polyfill from this package.

## Origin of the polyfill

The polyfill is derived from the original Symfony class:

- **Original (removed):**  
  `Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag`  
  Source: [symfony/http-foundation 5.4 – NamespacedAttributeBag.php](https://github.com/symfony/http-foundation/blob/5.4/Session/Attribute/NamespacedAttributeBag.php)

Adaptations made for this package:

- Namespace changed to `Sylius\Bundle\FlowBundle\Storage` so the class lives inside the bundle.
- Deprecation notice and `@deprecated` annotation removed.
- Type declarations updated for PHP 8+ (return types, `mixed`, etc.) where applicable.
- Behaviour of `has`, `get`, `set`, `remove`, `resolveAttributePath`, and `resolveKey` is unchanged so that existing session data and `SessionFlowsBag` usage remain compatible.

## Why keep it in the package

Keeping the polyfill inside the bundle (rather than in the application) ensures that:

- The bundle does not depend on application-specific code.
- Any project using this bundle gets a consistent, compatible implementation.
- Upgrades and maintenance of the polyfill stay with the bundle.

## References

- [Symfony 5.4 NamespacedAttributeBag source](https://github.com/symfony/http-foundation/blob/5.4/Session/Attribute/NamespacedAttributeBag.php)
- Symfony 5.3 deprecation: the class was deprecated in favour of using `AttributeBag` directly; however, `SessionFlowsBag` relies on the namespaced structure (e.g. for clearing by domain), so a direct replacement with `AttributeBag` would change session structure and break existing behaviour. Hence this polyfill is used instead.
