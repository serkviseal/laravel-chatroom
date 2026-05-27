variable "environment" {}
variable "region" {}

resource "digitalocean_spaces_bucket" "chatroom" {
  name   = "chatroom-${var.environment}-storage"
  region = var.region
  acl    = "private"

  cors_rule {
    allowed_headers = ["*"]
    allowed_methods = ["GET"]
    allowed_origins = ["*"]
    max_age_seconds = 3600
  }
}

resource "digitalocean_cdn" "chatroom" {
  origin = digitalocean_spaces_bucket.chatroom.bucket_domain_name
  ttl    = 3600
}

output "bucket_name" {
  value = digitalocean_spaces_bucket.chatroom.name
}

output "cdn_endpoint" {
  value = "https://${digitalocean_cdn.chatroom.endpoint}"
}
