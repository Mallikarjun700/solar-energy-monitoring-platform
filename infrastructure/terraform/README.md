# Terraform Infrastructure

This directory contains the AWS infrastructure for the Solar Energy Monitoring Platform.

## Required Tooling

- Terraform `1.16.0`
- AWS CLI configured with credentials only when AWS deployment is intentionally required

## Local Validation

These commands do not create AWS resources:

```bash
terraform init -backend=false
terraform fmt -check -recursive
terraform validate
