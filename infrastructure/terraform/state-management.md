# Terraform State Management

## Purpose

Terraform state records the resources managed by Terraform and their current
infrastructure attributes.

Terraform state must not be committed to Git.

## Production Architecture

Production Terraform state is designed to use Amazon S3 as the remote backend.

```text
GitHub Actions / Authorized Operator
                |
                v
             Terraform
                |
                v
       Amazon S3 State Bucket
                |
        +-------+-------+
        |               |
        v               v
terraform.tfstate   .tflock
        |
        v
     Versioning
        |
        v
Historical state recovery
