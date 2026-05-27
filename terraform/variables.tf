variable "do_token" {
  description = "DigitalOcean personal access token"
  type        = string
  sensitive   = true
}

variable "github_token" {
  description = "GitHub personal access token with repo + admin:org scopes"
  type        = string
  sensitive   = true
}

variable "github_owner" {
  description = "GitHub username or organization owning the repository"
  type        = string
}

variable "github_repo" {
  description = "GitHub repository name (without owner prefix)"
  type        = string
  default     = "laravel-chatroom"
}

variable "do_region" {
  description = "DigitalOcean region"
  type        = string
  default     = "nyc3"
}

variable "environment" {
  description = "Deployment environment: staging | production"
  type        = string
  default     = "staging"

  validation {
    condition     = contains(["staging", "production"], var.environment)
    error_message = "environment must be 'staging' or 'production'."
  }
}

variable "db_password" {
  description = "PostgreSQL application user password"
  type        = string
  sensitive   = true
}

variable "app_domain" {
  description = "Root domain for the application (e.g. chatroom.example.com)"
  type        = string
}

variable "ssh_fingerprint" {
  description = "SSH key fingerprint registered in DigitalOcean"
  type        = string
}
