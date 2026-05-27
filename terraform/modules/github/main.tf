variable "repo" {}
variable "owner" {}
variable "staging_host" { default = "" }
variable "prod_host" { default = "" }

# Branch protection: main requires PR + review + passing CI
resource "github_branch_protection" "main" {
  repository_id  = "${var.owner}/${var.repo}"
  pattern        = "main"
  enforce_admins = true

  required_pull_request_reviews {
    dismiss_stale_reviews      = true
    required_approving_review_count = 1
  }

  required_status_checks {
    strict   = true
    contexts = [
      "Code style (Pint)",
      "Static analysis (Larastan)",
      "Tests (Pest)",
      "Build assets (Vite)",
    ]
  }
}

# Branch protection: develop requires CI but not a review
resource "github_branch_protection" "develop" {
  repository_id = "${var.owner}/${var.repo}"
  pattern       = "develop"

  required_status_checks {
    strict   = false
    contexts = [
      "Code style (Pint)",
      "Tests (Pest)",
    ]
  }
}

# Secrets for deployment
resource "github_actions_environment_secret" "staging_host" {
  count           = var.staging_host != "" ? 1 : 0
  repository      = var.repo
  environment     = "staging"
  secret_name     = "STAGING_HOST"
  plaintext_value = var.staging_host
}

resource "github_actions_environment_secret" "prod_host" {
  count           = var.prod_host != "" ? 1 : 0
  repository      = var.repo
  environment     = "production"
  secret_name     = "PROD_HOST"
  plaintext_value = var.prod_host
}
