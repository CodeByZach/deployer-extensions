<?php
namespace Deployer;

use Deployer\Exception\Exception;


/**
 * Directory to store autossh socket files.
 * ```php
 * set('autossh_socket_directory', '/var/run/autossh');
 * ```
 */
set('autossh_socket_directory', '~/autossh');


/**
 * Local host for SSH tunnel forwarding.
 * ```php
 * set('autossh_local_host', '127.0.0.1');
 * ```
 */
set('autossh_local_host', 'localhost');


/**
 * Path to autossh log file.
 * ```php
 * set('autossh_log_file', '/var/log/autossh.log');
 * ```
 */
set('autossh_log_file', '~/autossh.log');


/**
 * Generate a unique ID for a given SSH tunnel based on connection parameters.
 */
function autosshTunnelId(string $host, string $username, $port, $local_port): string {
	$hash = md5($host . $username . $port . $local_port);
	return substr($hash, 0, 10);
}


/**
 * Get the socket file path for a tunnel.
 */
function autosshSocketFile(string $socket_directory, string $tunnel_id): string {
	return "{$socket_directory}/{$tunnel_id}.sock";
}


/**
 * Check if a local port is available for binding.
 */
function autosshIsPortAvailable(string $host, $port): bool {
	return test("\$({{bin/php}} -r \"\\\$socket = @stream_socket_server('tcp://{$host}:{$port}', \\\$errno, \\\$errstr); echo (\\\$socket ? 'true' : 'false');\")");
}


/**
 * Check if a tunnel is currently open.
 */
function autosshIsTunnelOpen(string $socket_file): bool {
	return test("[ $(pgrep -fc \"autossh.*{$socket_file}\") -gt 0 ]");
}


/**
 * Open an SSH tunnel using autossh.
 */
function autosshOpenTunnel(string $local_host, string $log_file, string $username, string $host, $port, string $key_file, $local_port, $remote_port, string $socket_file): bool {
	run("AUTOSSH_LOGFILE={$log_file} autossh -f -N -L {$local_port}:{$local_host}:{$remote_port} -o \"ControlPath={$socket_file}\" -p {$port} -i {$key_file} {$username}@{$host}");
	return autosshIsTunnelOpen($socket_file);
}


/**
 * Close an SSH tunnel.
 */
function autosshCloseTunnel(string $socket_file): bool {
	return test("[ $(pkill -fc \"autossh.*{$socket_file}\") -gt 0 ]");
}


/**
 * Extract tunnel parameters from config array and global settings.
 */
function autosshGetTunnelParams(array $tunnel_config): array {
	return [
		'local_host'       => get('autossh_local_host'),
		'log_file'         => get('autossh_log_file'),
		'socket_directory' => get('autossh_socket_directory'),
		'username'         => $tunnel_config['username'],
		'host'             => $tunnel_config['host'],
		'port'             => $tunnel_config['port'],
		'key_file'         => $tunnel_config['key_file'],
		'local_port'       => $tunnel_config['local_port'],
		'remote_port'      => $tunnel_config['remote_port'],
	];
}


/**
 * Provision autossh on the server.
 */
desc('Provision autossh');
task('provision:autossh', function () {
	// Ensure autossh is installed.
	$installed = commandExist('autossh');
	if (!$installed && askConfirmation('Autossh is not installed. Would you like to install it?')) {
		run('apt-get update && apt-get install -y autossh');

		// Check if the installation was successful.
		if (!commandExist('autossh')) {
			throw new Exception('Failed to install autossh');
		}

		writeSuccess('Autossh installed successfully');
	}

	// Ensure the log directory exists.
	$autossh_log_path = run("dirname {{autossh_log_file}}");
	if (test("[ ! -d {$autossh_log_path} ]")) {
		if (askConfirmation("Autossh log directory \"{$autossh_log_path}\" does not exist. Would you like to create it?")) {
			run("mkdir -p {$autossh_log_path}");
			writeSuccess("Autossh log directory created: {$autossh_log_path}");
		}
	}

	// Ensure the socket path exists.
	if (test('[ ! -d {{autossh_socket_directory}} ]')) {
		if (askConfirmation('Autossh socket directory "{{autossh_socket_directory}}" does not exist. Would you like to create it?')) {
			run('mkdir -p {{autossh_socket_directory}}');
			writeSuccess('Autossh socket directory created: {{autossh_socket_directory}}');
		}
	}
})->oncePerNode();


/**
 * Open configured SSH tunnels.
 */
desc('Opens ssh tunnels');
task('provision:autossh:open', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $tunnel_config) {
			$p = autosshGetTunnelParams($tunnel_config);

			$tunnel_id   = autosshTunnelId($p['host'], $p['username'], $p['port'], $p['local_port']);
			$socket_file = autosshSocketFile($p['socket_directory'], $tunnel_id);

			// Ensure the tunnel is not already open.
			if (!autosshIsTunnelOpen($socket_file)) {
				// Check if the local port is available.
				if (autosshIsPortAvailable($p['local_host'], $p['local_port'])) {
					// Tunnel is not open, local port is available, log and open it.
					autosshOpenTunnel($p['local_host'], $p['log_file'], $p['username'], $p['host'], $p['port'], $p['key_file'], $p['local_port'], $p['remote_port'], $socket_file) || throw new Exception("Failed to open SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
					writeSuccess("Successfully opened SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
				} else {
					// Local port is not available, log and abort.
					throw new Exception("Local port {$p['local_port']} is not available for SSH tunnel: {$p['username']}@{$p['host']} (Remote Port: {$p['remote_port']})");
				}
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
})->oncePerNode();


/**
 * Close configured SSH tunnels.
 */
desc('Closes ssh tunnels');
task('provision:autossh:close', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $tunnel_config) {
			$p = autosshGetTunnelParams($tunnel_config);

			$tunnel_id   = autosshTunnelId($p['host'], $p['username'], $p['port'], $p['local_port']);
			$socket_file = autosshSocketFile($p['socket_directory'], $tunnel_id);

			// Check if the tunnel is already open.
			if (autosshIsTunnelOpen($socket_file)) {
				// Tunnel is open, log and close it.
				autosshCloseTunnel($socket_file) || throw new Exception("Failed to close SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
				writeSuccess("Successfully closed SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
});


/**
 * List status of configured SSH tunnels.
 */
desc('Lists ssh tunnels');
task('provision:autossh:list', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $tunnel_config) {
			$p = autosshGetTunnelParams($tunnel_config);

			$tunnel_id   = autosshTunnelId($p['host'], $p['username'], $p['port'], $p['local_port']);
			$socket_file = autosshSocketFile($p['socket_directory'], $tunnel_id);

			// Check if the tunnel is open.
			if (autosshIsTunnelOpen($socket_file)) {
				writeSuccess("Active SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
			} else {
				writeWarning("Inactive SSH tunnel: {$p['username']}@{$p['host']} (Local Port: {$p['local_port']}, Remote Port: {$p['remote_port']})");
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
});


/**
 * Display autossh version.
 */
desc('Gets the autossh version');
task('provision:autossh:version', function () {
	$output = run("autossh -V");
	writeOutput($output);
});