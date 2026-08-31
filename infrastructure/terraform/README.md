# Terraform Infrastructure

This directory contains the AWS infrastructure for the solar energy monitoring platform.

## Required Tooling

- Terraform `1.16.0`
- AWS CLI configured with credentials for the target account

## Local Setup

If Terraform is not installed on your machine, install it with Homebrew:

```bash
brew tap hashicorp/tap
brew install hashicorp/tap/terraform
```

If Homebrew reports outdated Command Line Tools, update Xcode Command Line Tools first and rerun the install.

## Validate

From this directory:

```bash
terraform init
terraform fmt -check
terraform validate
```

## Deploy

Use the environment-specific variable files under `environments/` when planning or applying:

```bash
terraform plan -var-file=environments/staging.tfvars
terraform apply -var-file=environments/staging.tfvars
```
