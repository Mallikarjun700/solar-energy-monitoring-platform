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
