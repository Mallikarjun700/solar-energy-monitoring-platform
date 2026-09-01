# Terraform State Management

## Purpose

Terraform state records the resources managed by Terraform and their current
infrastructure attributes.

Terraform state must not be committed to Git.

## Local Development

Local Terraform commands may use local state during development and validation.

State files are excluded from version control.

## Production Architecture

Production deployments should use a remote Amazon S3 backend.

```text
GitHub Actions
      |
      v
Terraform
      |
      v
Amazon S3
Terraform State
