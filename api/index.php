<?php
// Proxy entry point for Vercel deployment
// This file satisfies the Vercel requirement of having PHP functions in an 'api' directory

// 1. Move the execution pointer to the project root
chdir(__DIR__ . '/../');

// 2. Include the main application file
require_once 'index.php';
