## Camptime

Camptime is a simple command (using Symfony Console) that allows you to quickly enter time into Basecamp Classic.


### Setup (non Laravel)

First, clone the repo.

	git clone git@github.com:danielkperez/camptime.git

Next, create a .env file and fill in your Basecamp Base URL and Basecamp Classic API key. You can use the .env.example file as a starting point.

To get your API key, log into Basecamp, click the "My info" link, and scroll to the bottom of the page. Next, click the "Show your tokens" link and your 40-character key will be revealed to you.

### Setup (Laravel)

Install the package via composer:

    composer require danielkperez/camptime

Include the service provider in config/app.php:

    'providers' => [
        ...
        Camptime\CamptimeServiceProvider::class
        ...
    ];

### Running the command (non Laravel)

Once your API key is in place, open up the Terminal and `cd` into the root directory of your cloned Camptime repository.

You can then log time with the Camptime engine with `camptime logtime` using the following command:

    camptime logtime

### Running the command (Laravel)

One your API key and service provider is in place, you can log time with the Camptime engine with `logtime`

    logtime

You'll see a list of all the active projects in your Basecamp account that you have access to.

Entering time for a project is easy. Simply put the Project's ID and type an entry into the prompt like so:

	12345|1.5|Added to-do sorting

Press `enter` and an entry for 1 1/2 hours will be added for project ID 12345 with a description that reads "`Added to-do sorting`".

If you're adding time for the current day, you don't need to enter a date. However, you can override this with a 4th "argument", like so:

	12345|1.5|Added to-do sorting|2015-09-01

After each entry, you'll be able to enter another entry. To quit the application, type `CTRL-c`.