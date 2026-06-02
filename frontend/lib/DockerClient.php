<?php

class DockerClient {
    private $dockerHost;

    public function __construct() {
        // Fallback to standard socket if not set
        $this->dockerHost = getenv('DOCKER_HOST') ?: 'unix:///var/run/docker.sock';
    }

    private function query($path, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        if (strpos($this->dockerHost, 'unix://') === 0) {
            $socketPath = substr($this->dockerHost, 7);
            curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socketPath);
            $url = "http://localhost" . $path;
        } else {
            // Assume TCP (e.g. http://localhost:2375)
            $url = rtrim($this->dockerHost, '/') . $path;
            if (strpos($url, 'http') !== 0) {
                $url = 'http://' . $url;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Docker API Error: " . $error);
            return null;
        }

        if ($httpCode >= 400) {
            error_log("Docker API HTTP Error: " . $httpCode . " - " . $response);
            return null;
        }

        return json_decode($response, true) ?? true;
    }

    public function getContainers() {
        return $this->query('/containers/json?all=1') ?: [];
    }

    public function getStacks() {
        $containers = $this->getContainers();
        $stacks = [];

        foreach ($containers as $container) {
            $project = $container['Labels']['com.docker.compose.project'] ?? 'Standalone';
            if (!isset($stacks[$project])) {
                $stacks[$project] = [
                    'name' => $project,
                    'containers' => [],
                    'status' => 'stopped',
                    'active_count' => 0,
                    'total_count' => 0
                ];
            }
            $stacks[$project]['containers'][] = $container;
            $stacks[$project]['total_count']++;
            if ($container['State'] === 'running') {
                $stacks[$project]['active_count']++;
            }
        }

        foreach ($stacks as &$stack) {
            if ($stack['active_count'] === $stack['total_count'] && $stack['total_count'] > 0) {
                $stack['status'] = 'running';
            } elseif ($stack['active_count'] > 0) {
                $stack['status'] = 'partial';
            } else {
                $stack['status'] = 'stopped';
            }
        }

        ksort($stacks);
        return $stacks;
    }

    public function startStack($projectName) {
        $containers = $this->getContainers();
        $success = true;
        foreach ($containers as $container) {
            $proj = $container['Labels']['com.docker.compose.project'] ?? 'Standalone';
            if ($proj === $projectName) {
                if ($this->query("/containers/{$container['Id']}/start", 'POST') === null) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    public function stopStack($projectName) {
        $containers = $this->getContainers();
        $success = true;
        foreach ($containers as $container) {
            $proj = $container['Labels']['com.docker.compose.project'] ?? 'Standalone';
            if ($proj === $projectName) {
                if ($this->query("/containers/{$container['Id']}/stop", 'POST') === null) {
                    $success = false;
                }
            }
        }
        return $success;
    }

    public function restartStack($projectName) {
        $containers = $this->getContainers();
        $success = true;
        foreach ($containers as $container) {
            $proj = $container['Labels']['com.docker.compose.project'] ?? 'Standalone';
            if ($proj === $projectName) {
                if ($this->query("/containers/{$container['Id']}/restart", 'POST') === null) {
                    $success = false;
                }
            }
        }
        return $success;
    }
}
