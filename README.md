# PHP-Mini-File-Browser

[My writeup about this tool](https://smitka.me/2024/04/12/php-mini-file-browser-update/)

This is really simple&primitive and dangerous script which allows you:
- iterate throw directory structure and show permissions, it uses 2 methods:
   - plain PHP which can be limited via open_basedir
   - shell_exec system function which can be limited by disabled_functions
- show basic info about PHP configuraion (version, extensions, disable functions, open_basedir, or complete phpinfo)
- download files from the server (if enabled)
- upload files from URL to the server (if enabled)
- read files and show their content (text, images, archives content)
- run system commands via various methods (if enabled)

The script will **delete itself after 1 hour** for security reasons (you can configure this behavior). It is also possible to set credentials to use this script, of course.

> [!CAUTION]
> Do not grant “MFB” access to untrusted users, as a skilled user could escalate their privileges and do anything to your site and server 😉. The script is full of security threats and can cause FPD, XSS, SQLi, SSRF, LFI, RCE, WTF, etc.

## File browser
![mfb-file-browser](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/29802d82-7509-463e-b874-5cefd32350d6)

## Dark Mode 😎
![mfb-file-browser-dark](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/57d9e92f-bb36-4414-a33d-54f145c4977c)


## Command executor
![mfb-command-executor](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/50f782ba-ec29-4099-86cc-b02ab803a097)

## File uploader
![mfb-file-uploader](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/36438b8e-0609-4ba8-8d09-a42c2cb8a82f)

## File reader

View text files content

![mfb-file-reader-text](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/95e5f783-3596-44c0-bb72-67002ad5619b)

Show images

![mfb-file-reader-image](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/63455604-b4d5-4d0e-a996-0e56524db465)

Show files inside archive (zip, tar, tgz)

![mfb-file-reader-archive](https://github.com/lynt-smitka/PHP-Mini-File-Browser/assets/3875093/82503901-ea23-45de-9fab-dad2920ee0cb)


        
*Note: this project is still alive :-)*
