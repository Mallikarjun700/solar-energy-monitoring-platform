# Deployment Flow

The repository contains a CI pipeline with automated Render deployment and a separate AWS ECS/Fargate deployment workflow.

## Current Render path

```text
Developer
   |
   v
Push / Pull Request
   |
   v
GitHub Actions
   |
   +--> Angular tests + build
   +--> Laravel tests
   +--> Docker builds + smoke tests
   +--> Terraform fmt/init/validate
   |
   v
Push to main
   |
   v
Render API
   |
   +--> Backend service
   |
   +--> Frontend service
   |
   v
Wait for deployment status
   |
   +--> live → success
   +--> build/update/pre-deploy/canceled → fail
```

## CI gates

The main CI workflow validates:

1. Angular tests and production build.
2. Laravel syntax, Pint, and PHPUnit.
3. Docker PHP image build and smoke test.
4. Docker Nginx image build and smoke test.
5. Docker Compose configuration.
6. Terraform formatting and validation.

Deployment runs only for a push to `main` after the required jobs succeed.

## Render deployment

The workflow uses the Render API with:

- `RENDER_API_KEY` GitHub secret
- backend Render service ID
- frontend Render service ID
- current commit SHA

The workflow starts a deployment and polls until Render reports `live` or a failure state.

The workflow fails rather than reporting success when Render returns:

- `build_failed`
- `update_failed`
- `pre_deploy_failed`
- `canceled`

## AWS deployment path

The repository also contains AWS deployment automation using:

```text
GitHub Actions
   |
   v
AWS OIDC authentication
   |
   v
ECR
   |
   +--> backend image
   +--> Nginx image
   |
   v
ECS/Fargate
   |
   +--> backend
   +--> queue worker
   +--> scheduler
   |
   v
ALB
   |
   v
Health verification
```

Terraform provisions the AWS infrastructure.

The AWS path should be treated as a separate deployment target from Render.

## Migration and verification

The AWS workflow includes a migration task before service rollout and verifies:

- ECS deployment state
- ALB target health
- application readiness
- deployed image tags

## Rollback principle

A failed deployment must not be considered successful merely because the CI job reached the deployment step.

Use the platform's deployment history and previous known-good image/commit to perform a controlled rollback.

## Source of truth

- `.github/workflows/ci.yml` — Render CI/CD
- `.github/workflows/` AWS workflow — ECS deployment automation
- `infrastructure/terraform/` — AWS infrastructure
- [AWS Deployment Runbook](../architecture/aws/deployment-runbook.md)
