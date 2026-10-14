-- Run this once in phpMyAdmin if the users table already exists.
USE narrativestudios;
ALTER TABLE users ADD COLUMN face_descriptor JSON NULL;
