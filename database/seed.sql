-- ============================================================================
-- Lectura - Initial data
-- Starts with exactly one administrator and no course content.
-- Admin: alaa.m.swidi@gmail.com
-- Temporary password: Alaa@2026!  (change it immediately after first login)
-- ============================================================================

SET NAMES utf8mb4;

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `lang`, `theme`, `is_active`, `email_notify`, `created_at`) VALUES
(1, 'Alaa Swidi', 'alaa.m.swidi@gmail.com', '$2y$10$a6OKrGLXjZeuAQFlZUbo8.aUvJgApwAYFtzqBPaVPL0NW1j6APlfu', 'admin', 'ar', 'light', 1, 1, UTC_TIMESTAMP());
