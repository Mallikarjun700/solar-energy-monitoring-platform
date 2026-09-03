# Dependency & Version Management

## 1. Purpose

This document defines the standards for managing software dependencies and technology versions across the Solar Energy Monitoring & Asset Management Platform.

The objective is to ensure that dependencies are:

- Known
- Reproducible
- Secure
- Supported
- Intentionally upgraded
- Compatible with the platform
- Recoverable when upgrades fail

Dependency management is considered part of production engineering and supply-chain security.

---

## 2. Scope

These standards apply to:

- PHP
- Laravel
- Composer packages
- Node.js
- Angular
- npm packages
- Docker base images
- Docker runtime dependencies
- Terraform
- Terraform providers
- Terraform modules
- AWS SDKs
- CI/CD actions
- Testing libraries
- Development tooling
- Operating-system packages

---

## 3. Dependency Principles

The platform follows these principles:

1. Prefer supported versions.
2. Pin important infrastructure versions.
3. Commit dependency lock files.
4. Avoid unnecessary dependencies.
5. Keep dependencies regularly updated.
6. Treat security vulnerabilities as engineering issues.
7. Test upgrades before production rollout.
8. Avoid uncontrolled transitive dependencies.
9. Prefer reproducible builds.
10. Remove obsolete dependencies.
11. Document significant version decisions.
12. Maintain rollback capability.

---

## 4. Dependency Ownership

Every production dependency should have an identifiable engineering owner.

Ownership includes responsibility for:

- Understanding the dependency purpose
- Monitoring security advisories
- Reviewing upgrades
- Managing breaking changes
- Removing obsolete dependencies

Dependencies without a clear purpose should be candidates for removal.

---

## 5. Direct Dependencies

Direct dependencies are explicitly required by application code or infrastructure.

Examples include:

- Laravel packages
- Angular packages
- Database drivers
- Redis clients
- AWS SDKs
- Terraform providers

Direct dependencies should be intentionally selected and documented when they have significant architectural impact.

---

## 6. Transitive Dependencies

Transitive dependencies are introduced indirectly by direct dependencies.

They should not be manually managed unless required.

Security and compatibility checks must nevertheless account for the complete dependency tree.

---

## 7. Version Pinning

Production-critical technologies should use controlled versions.

Examples:

- PHP runtime
- Node.js runtime
- Laravel
- Angular
- Terraform
- Terraform providers
- Docker base images

Avoid relying on uncontrolled moving targets such as:

- `latest`
- Floating Docker tags
- Unbounded major versions

---

## 8. Semantic Versioning

Where a dependency follows Semantic Versioning:

`MAJOR.MINOR.PATCH`

should be interpreted as:

- MAJOR — potentially breaking changes
- MINOR — backward-compatible features
- PATCH — backward-compatible fixes

Version changes must still be reviewed because not every upstream project follows semantic versioning perfectly.

---

## 9. Lock Files

Dependency lock files must be committed to source control where the package manager supports them.

Examples:

- `composer.lock`
- `package-lock.json`

Lock files provide:

- Reproducible builds
- Deterministic dependency resolution
- Controlled transitive versions
- Easier rollback
- Reduced environment drift

---

## 10. Reproducible Builds

Builds should produce consistent dependency versions across:

- Developer environments
- CI
- Staging
- Production

The build process must not silently resolve different dependency versions between environments.

---

## 11. PHP Version

The PHP version used by:

- Local development
- CI
- Docker
- Production

should be explicitly defined and kept aligned.

PHP upgrades require compatibility testing across:

- Laravel
- Composer dependencies
- Application code
- Extensions
- Test suites

---

## 12. Laravel Version

Laravel versions should be managed deliberately.

Before upgrading Laravel:

1. Review supported PHP versions.
2. Review framework upgrade documentation.
3. Review package compatibility.
4. Run automated tests.
5. Run static analysis where configured.
6. Validate database behavior.
7. Validate queue behavior.
8. Validate API behavior.
9. Validate deployment behavior.

Major Laravel upgrades require explicit review.

---

## 13. Composer Dependencies

Composer dependencies should be managed through `composer.json` and `composer.lock`.

Use Composer commands appropriate to the intended change.

Avoid manually editing the lock file.

Dependency upgrades should be isolated into reviewable changes.

---

## 14. Composer Production Installation

Production builds should install production dependencies without unnecessary development packages.

Production dependency installation should be deterministic.

Builds should fail if dependency resolution or integrity validation fails.

---

## 15. Node.js Version

The Node.js version used for Angular development and CI must be explicitly controlled.

The selected version should be compatible with:

- Angular version
- npm version
- Build tooling
- CI runners

Runtime upgrades must be tested before adoption.

---

## 16. Angular Version

Angular upgrades should be planned rather than performed opportunistically.

Before an Angular major upgrade:

- Review Angular compatibility requirements.
- Review TypeScript compatibility.
- Review RxJS compatibility.
- Review build tooling.
- Run frontend tests.
- Run production builds.
- Validate browser behavior.

---

## 17. npm Dependencies

Frontend dependencies must be managed through:

- `package.json`
- `package-lock.json`

Dependencies should not be installed with unrestricted floating versions in production builds.

---

## 18. npm Production Builds

CI should use deterministic installation based on the committed lock file.

Production builds must fail when dependency lock state is inconsistent with the declared dependency configuration.

---

## 19. Terraform Version

Terraform version must be explicitly controlled.

The project should define its required Terraform version using infrastructure configuration and/or version-management files.

Developers and CI should use the approved Terraform version.

---

## 20. Terraform Provider Versions

Terraform providers should use controlled version constraints.

Provider upgrades require:

- Review of release notes
- Terraform validation
- Plan review
- Compatibility testing
- State-impact review where applicable

Provider upgrades should not be performed automatically in production without review.

---

## 21. Terraform Modules

External Terraform modules should be versioned where possible.

Avoid consuming arbitrary moving references.

Module upgrades must be reviewed for:

- Resource changes
- Security changes
- State changes
- Breaking changes
- Provider compatibility

---

## 22. Docker Base Images

Docker images should use controlled base-image versions.

Avoid:

```text
latest
