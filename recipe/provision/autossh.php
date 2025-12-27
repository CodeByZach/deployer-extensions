<?php
namespace Deployer;

use Deployer\Exception\Exception;


// Set default variables.
set('autossh_socket_directory', '~/autossh');
set('autossh_local_host',       'localhost');
set('autossh_log_file',         '~/autossh.log');


// Generate a unique ID for a given ssh tunnel.
function autosshTunnelId($host, $username, $port, $local_port) {
	$hash = md5($host.$username.$port.$local_port);
	return substr($hash, 0, 10); // Returns only the first 10 characters (out of 32) of the hash.
}


// Get the socket file path.
function autosshSocketFile($socket_directory, $tunnel_id) {
	return "{$socket_directory}/{$tunnel_id}.sock";
}


// Check if port is available.
function autosshIsPortAvailable($host, $port) {
	return test("\$({{bin/php}} -r \"\\\$socket = @stream_socket_server('tcp://{$host}:{$port}', \\\$errno, \\\$errstr); echo (\\\$socket ? 'true' : 'false');\")");
}


// Is tunnel already open.
function autosshIsTunnelOpen($socket_file) {
	return test("[ $(pgrep -fc \"autossh.*{$socket_file}\") -gt 0 ]");
}


// Open tunnel.
function autosshOpenTunnel($local_host, $log_file, $username, $host, $port, $key_file, $local_port, $remote_port, $socket_file) {
	run("AUTOSSH_LOGFILE={$log_file} autossh -f -N -L {$local_port}:{$local_host}:{$remote_port} -o \"ControlPath={$socket_file}\" -p {$port} -i {$key_file} {$username}@{$host}");
	return autosshIsTunnelOpen($socket_file);
}


// Close tunnel.
function autosshCloseTunnel($socket_file) {
	return test("[ $(pkill -fc \"autossh.*{$socket_file}\") -gt 0 ]");
}


// Configure the instance for tunneling via autossh.
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


// Open ssh tunnels.
desc('Opens ssh tunnels');
task('provision:autossh:open', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $key => $tunnel_config) {
			$local_host       = get('autossh_local_host');
			$log_file         = get('autossh_log_file');
			$socket_directory = get('autossh_socket_directory');
			$username         = $tunnel_config['username'];
			$host             = $tunnel_config['host'];
			$port             = $tunnel_config['port'];
			$key_file         = $tunnel_config['key_file'];
			$local_port       = $tunnel_config['local_port'];
			$remote_port      = $tunnel_config['remote_port'];

			$tunnel_id   = autosshTunnelId($host, $username, $port, $local_port);
			$socket_file = autosshSocketFile($socket_directory, $tunnel_id);

			// Ensure the tunnel is not already open.
			if (!autosshIsTunnelOpen($socket_file)) {
				// Check if the local port is available.
				if (autosshIsPortAvailable($local_host, $local_port)) {
					// Tunnel is not open, local port is available, log and open it.
					autosshOpenTunnel($local_host, $log_file, $username, $host, $port, $key_file, $local_port, $remote_port, $socket_file) || throw new Exception("Failed to open SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
					writeSuccess("Successfully opened SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
				} else {
					// Local port is not available, log and abort.
					throw new Exception("Local port {$local_port} is not available for SSH tunnel: {$username}@{$host} (Remote Port: {$remote_port})");
				}
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
})->oncePerNode();


// Close ssh tunnels.
desc('Closes ssh tunnels');
task('provision:autossh:close', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $key => $tunnel_config) {
			$local_host       = get('autossh_local_host');
			$log_file         = get('autossh_log_file');
			$socket_directory = get('autossh_socket_directory');
			$username         = $tunnel_config['username'];
			$host             = $tunnel_config['host'];
			$port             = $tunnel_config['port'];
			$key_file         = $tunnel_config['key_file'];
			$local_port       = $tunnel_config['local_port'];
			$remote_port      = $tunnel_config['remote_port'];

			$tunnel_id   = autosshTunnelId($host, $username, $port, $local_port);
			$socket_file = autosshSocketFile($socket_directory, $tunnel_id);

			// Check if the tunnel is already open.
			if (autosshIsTunnelOpen($socket_file)) {
				// Tunnel is open, log and close it.
				autosshCloseTunnel($tunnel_id) || throw new Exception("Failed to close SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
				writeSuccess("Successfully closed SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
});


// Get list of ssh tunnels.
desc('Lists ssh tunnels');
task('provision:autossh:list', function () {
	if (has('autossh')) {
		// invoke('provision:autossh');

		foreach (get('autossh') as $key => $tunnel_config) {
			$local_host       = get('autossh_local_host');
			$log_file         = get('autossh_log_file');
			$socket_directory = get('autossh_socket_directory');
			$username         = $tunnel_config['username'];
			$host             = $tunnel_config['host'];
			$port             = $tunnel_config['port'];
			$key_file         = $tunnel_config['key_file'];
			$local_port       = $tunnel_config['local_port'];
			$remote_port      = $tunnel_config['remote_port'];

			$tunnel_id   = autosshTunnelId($host, $username, $port, $local_port);
			$socket_file = autosshSocketFile($socket_directory, $tunnel_id);

			// Check if the tunnel is open.
			if (autosshIsTunnelOpen($socket_file)) {
				writeSuccess("Active SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
			} else {
				writeWarning("Inactive SSH tunnel: {$username}@{$host} (Local Port: {$local_port}, Remote Port: {$remote_port})");
			}
		}
	} else {
		writeWarning("Missing/empty tunnel configuration");
	}
});


// Get the autossh version.
desc('Gets the autossh version');
task('provision:autossh:version', function () {
	$output = run("autossh -V");
	writeOutput($output);
});