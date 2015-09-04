## Camptime

Camptime is a simple command (using Laravel Lumen) that allows you to quickly enter time into Basecamp Classic.

### Setup

After cloning the repo, create a .env file and fill in your Basecamp Classic API key. 

To get your API key, log into Basecamp, click the "My info" link, and scroll to the bottom of the page. Next, click the "Show your tokens" link and your 40-character key will be revealed to you.

### Running the command

Once your API key is in place, open up the Terminal and `cd` into the root directory of your cloned Camptime repository.

You can then start up the Camptime engine with `artisan` using the following command:

	`php artisan camptime`

You'll see a list of all the active projects in your Basecamp account that you have access to. 

Entering time for a project is easy. Simply not the Project's ID and type an entry into the prompt like so:

	12345|1.5|Added to-do sorting

Press `enter` and an entry for 1 1/2 hours will be added for project ID 12345 with a description that reads "`Added to-do sorting`".

If you're adding time for the current day, you don't need to enter a date. However, you can override this with a 4th "argument", like so:

	12345|1.5|Added to-do sorting|2015-09-01
