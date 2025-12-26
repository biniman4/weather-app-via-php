<?php
// This is the entry point for Vercel deployment.
// It ensures that the app runs correctly within Vercel's serverless function structure.

// Point to the project root so scripts can find required files.
chdir(__DIR__ . '/../');

// Load the main application.
require_once 'index.php';
