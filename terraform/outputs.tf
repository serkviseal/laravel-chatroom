output "app_ip" {
  description = "Public IP address of the app droplet"
  value       = module.droplet.ip_address
}

output "db_host" {
  description = "PostgreSQL private host"
  value       = module.database.host
  sensitive   = true
}

output "redis_host" {
  description = "Redis private host"
  value       = module.redis.host
  sensitive   = true
}

output "spaces_bucket" {
  description = "DigitalOcean Spaces bucket name"
  value       = module.spaces.bucket_name
}

output "spaces_endpoint" {
  description = "Spaces CDN endpoint URL"
  value       = module.spaces.cdn_endpoint
}
