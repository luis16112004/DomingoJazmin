Route::get('/debug', function () {
    try {
        // Verificar variables de entorno
        $hasCredentialsEnv = !empty(env('FIREBASE_CREDENTIALS_JSON'));
        $credentialsLength = $hasCredentialsEnv ? strlen(env('FIREBASE_CREDENTIALS_JSON')) : 0;
        
        // Verificar archivo
        $credentialsPath = base_path('firebase_credentials.json');
        $hasCredentialsFile = file_exists($credentialsPath);
        
        // Verificar si podemos crear el archivo desde env
        $canCreateFromEnv = false;
        if ($hasCredentialsEnv) {
            try {
                $tempPath = sys_get_temp_dir() . '/firebase_test.json';
                file_put_contents($tempPath, env('FIREBASE_CREDENTIALS_JSON'));
                $canCreateFromEnv = file_exists($tempPath);
                if ($canCreateFromEnv) {
                    $content = json_decode(file_get_contents($tempPath), true);
                    $hasPrivateKey = isset($content['private_key']);
                    unlink($tempPath);
                } else {
                    $hasPrivateKey = false;
                }
            } catch (\Exception $e) {
                $hasPrivateKey = false;
            }
        } else {
            $hasPrivateKey = false;
        }
        
        return response()->json([
            'status' => 'debug_info',
            'firebase' => [
                'env_variable_exists' => $hasCredentialsEnv,
                'env_variable_length' => $credentialsLength,
                'file_exists' => $hasCredentialsFile,
                'can_create_from_env' => $canCreateFromEnv,
                'has_private_key' => $hasPrivateKey,
                'database_url' => env('FIREBASE_DATABASE_URL'),
            ],
            'app' => [
                'env' => app()->environment(),
                'debug' => config('app.debug'),
                'key_set' => !empty(config('app.key')),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'temp_dir' => sys_get_temp_dir(),
                'temp_writable' => is_writable(sys_get_temp_dir()),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});