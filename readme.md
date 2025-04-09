# HIBP Pwned Passwords Downloader (PHP)

This script downloads the complete set of [Have I Been Pwned](https://haveibeenpwned.com/) password hash **range files** using the 
[k-Anonymity model](https://haveibeenpwned.com/API/v3#PwnedPasswords) and saves them locally for offline querying or bulk analysis.

## Features

- No external dependencies except for php and ext-curl
- Fully automated download of all `16^5` (1,048,576) hash prefixes.
- Parallel downloads in batches
- Intelligent retry handling
- Resumable: skips already-downloaded ranges.
- Includes tools to compile the result to a binary file which is 48% smaller and cli or server tools to search the binary index.

## Requirements

* PHP 7.1 or later with cURL extension enabled or docker installed.
* 80GB of disk space. (Uncompressed download is ~49GB. Processed binary lookup file is ~27GB.)

## Usage

### Download Index.

Either `php tools/download.php`

or 

`bash tools/download.sh`

* Both tools work. The bash version is a little faster but the php version is the fastest resume.

* Expect weird things to happen. You're triggering a download of 1M+ files 100 at a time. It takes a bit.
    This tool can exhaust a cheap routers file handles, cause network glitches or generally reveal problems with your network setup.

* Expect this to take an hour even with a fast connection.

* The final product will be > 58GB

### Convert your text index to a binary one.

`php combine-to_bin.php`

* Reading and combining 1M+ files takes time. Right now its around 4o minutes.
* Once you have your binary in place, you can try out the search or server options.

### Search the database.

`php ./tools/search-bin.php 000A7036BC418BFF2F7FEF93C36BC7A748B55DBE`

### Start the dev server

`php -S 127.0.0.1:8080 -t ./public`

The open a web browser to http://127.0.0.1:8080?hash=000A7036BC418BFF2F7FEF93C36BC7A748B55DBE


## Notes

* This script respects HIBP's usage policy — includes a custom User-Agent header.
* Be considerate with API usage: the full download can be bandwidth and disk intensive (~50GB uncompressed).
* You may optionally add a short delay between batches (e.g., sleep(1)) to reduce server load.
* Enable logging in config.php only if needed. It will slow down all operations. (download,search and server)
* Since the data is sorted, our search functions use a binary search to look for your hash instead of scanning the whole list.

## 📄 License

MIT License

Copyright (c) 2025 Derak Kilgo

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

## Thank You

to Troy Hunt for [HIBP API](https://www.troyhunt.com/introducing-306-million-freely-downloadable-pwned-passwords/), 
his own dotnet powered [Downloader project](https://github.com/HaveIBeenPwned/PwnedPasswordsDownloader) 
and encouraging [other implementations.](https://github.com/HaveIBeenPwned/PwnedPasswordsDownloader/issues/79).