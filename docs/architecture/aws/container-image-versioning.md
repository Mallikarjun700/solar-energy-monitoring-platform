# Container Image Versioning

## Purpose

The Solar Energy Monitoring Platform uses immutable Docker image tags for AWS deployment.

## Image Repositories

Two ECR repositories are used:

- Backend Laravel/PHP image
- Nginx image

Both repositories use immutable image tags.

## Image Tag Strategy

The default Terraform values use the `demo` tag so the infrastructure remains
easy to demonstrate locally.

For an actual deployment, CI/CD should use the Git commit SHA as the image tag.

Example:

```text
backend:<git-sha>
nginx:<git-sha>
