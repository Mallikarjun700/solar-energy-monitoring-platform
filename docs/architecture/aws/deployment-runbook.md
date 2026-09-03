# AWS Deployment Runbook

## Purpose

This document describes the future deployment process for the Solar Energy Monitoring Platform.

The repository is deployment-ready, but AWS deployment is intentionally not executed as part of the current demo development workflow.

---

## 1. Prerequisites

Before deployment, the AWS environment should provide:

- AWS account
- AWS region
- GitHub repository
- IAM OIDC provider for GitHub Actions
- Deployment IAM role
- ECR repositories
- ECS cluster
- VPC and networking
- Application data stores
- Required AWS Secrets Manager secrets

Terraform is responsible for provisioning infrastructure.

---

## 2. Authentication

GitHub Actions should authenticate to AWS using GitHub OIDC.

```text
GitHub Actions
      |
      | OIDC token
      v
AWS IAM OIDC Provider
      |
      v
Deployment IAM Role

---

## 7. GitHub Environment Protection

GitHub Environments are used to separate staging and production deployments.

### Staging

The `staging` environment should contain:

- AWS deployment role ARN
- ECR backend repository
- ECR Nginx repository
- ECS cluster name
- Backend task family
- Backend service name
- Queue worker task family
- Queue worker service name
- Scheduler task family
- Scheduler service name

Staging deployment does not require manual approval unless the repository
policy requires it.

### Production

The `production` environment should contain its own environment-specific
values.

Production should require:

- Deployment approval from designated reviewers
- Deployment from the `main` branch only
- The production AWS deployment role
- Production ECR repositories
- Production ECS resources

Production credentials and environment variables must not be shared with
staging.

The deployment workflow also validates that production deployments originate
from the `main` branch.

### Security Principle

GitHub Actions uses short-lived AWS credentials obtained through OIDC.

No long-lived AWS access keys should be stored in GitHub repository or
environment secrets.
