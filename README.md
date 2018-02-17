# marquis
A simple way to point local domains to internal ports

## Introduction

Develop on Mac OS and love [docker](https://www.docker.com) or [vagrant](https://www.vagrantup.com) for local development? 

Sick to death of remembering port numbers you have mapped to your VM's or containers?  Join the club.

Marquis is wrapper around [DnsMasq](https://en.wikipedia.org/wiki/Dnsmasq) and [nginx](https://www.nginx.com) for Mac developers to point a local domain name to a port running on localhost.  

## Installation

Marquis requires macOS and [Homebrew](http://brew.sh/). Before installation, you should make sure that no other programs such as Apache or Nginx are binding to your local machine's port 80

- Install or update [Homebrew](http://brew.sh/) to the latest version using brew update.
- Install PHP > 5.6 using Homebrew. e.g.```$ brew install homebrew/php/php72```
- Install Marquis with Composer via ```$ composer global require alexwight/marquis```. Make sure the ~/.composer/vendor/bin directory is in your system's "PATH".
- Run the ```$ marquis install``` command. This will configure and install Nginx and DnsMasq.

## Usage Examples

### HTTP ###

To access target at ```http://localhost:8080```

```$ marquis site myapp http 8080```

You can now access at ```http://myapp.test```

### HTTPS ###

To access target at ```https://localhost:8443```

```$ marquis site myapp https 8443```

You can now access at ```https://myapp.test```

SSL Certificate is automatically generated and trusted.  HTTP/2 enabled by default.

## Problems?

I'm just using this for myself and putting this up there incase anyone else wants it.  If you are using this then I assume you are a developer.  Fix it and submit a pull request.

## License

Marquis is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT)
