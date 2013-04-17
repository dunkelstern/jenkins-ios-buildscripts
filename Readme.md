#Jenkins Autodeployment

To Use do the following:

## Webserver

You'll need PHP 5.2+, ZipArchive, MBString

1. Change to the server directory and install [composer](http://getcomposer.org):
~~~bash
    curl -sS https://getcomposer.org/installer | php
    php composer.phar install
~~~
 
2. Edit `.htaccess` if you install into a subdir
3. Edit `.htusers`, this is an INI-style file, not a regular `.htusers` (but Apache disallows download of `.htusers` so I chose that name)
    Structure is as follows:
    - [`<username>`]
    - salt=`<random string>`
    - password=`<md5 of your password + appended salt>`
    
	The default user is `demo`, password is `demo`
4. Rename the `projects/test` directory and edit `project.ini`, it needs only two keys: `project_name` is the display-name and `app_name` is the basename of the app. But if you want you may add `jira_link` with a URL to your Bugtracker and `jira_name` with the project shortcut the script will then link bug-IDs to the corresponding bugtracker entry.
2. Copy the contents of `server/` to the Webroot

## Jenkins
1. Add build.rb and Gemfile to your iOS Project dir (no need to add it to the Xcode project though, but check it into your SCM)
2. Export your Developer Identity and Provisioning Profiles to use for the build, do **NOT** add them to your SCM!
3. Add the contents of `jenkins_build_phase.sh` to your jenkins build process
4. Modify the path to your exported Developer credentials
5. Setup [Homebrew](http://mxcl.github.io/homebrew/) and [RVM](http://rvm.io) and create a Gemset named `jenkins` for the User running jenkins:
~~~bash
	ruby -e "$(curl -fsSL https://raw.github.com/mxcl/homebrew/go)"
	curl -L https://get.rvm.io | bash -s stable --autolibs=enabled --ruby=2.0.0
	# now restart your terminal
	rvm use 2.0.0
	rvm gemset create jenkins
~~~

6. Copy `logparser.rb` somewhere in your `PATH`
7. Add `jenkins_promote_action.sh` to your promote action or right after the build.
8. Setup Artifact deployment (I use the "Promoted Builds" and "Publish over FTP"  Plugins) to the webserver (upload the ipa into the `projects/<yourproject>`-directory), you might want to include the HTML File that you generated in the previous step as it contains the Git changelog and the PHP-Script will display it along with your build.

Now you can add `*.ipa`-Files via promoted builds to the `projects/<yourproject>/` directory and browse them with any Webbrowser, if you visit the page from a iOS-Device and the device is correctly provisioned you can directly install the App.