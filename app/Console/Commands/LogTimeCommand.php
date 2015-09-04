<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use SimpleXMLElement;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

class LogTimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'camptime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start up Camptime';
    
    public function __construct()
    {
        $this->client = new Client(
            ['base_uri' => 'https://kimbia.basecamphq.com/'
        ]);
        $this->apiKey = env('BASECAMP_API_KEY');

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->setup();
    }

    /**
     * Do some basic setup stuff
     */
    protected function setup()
    {
        $this->me();
        $this->projects = $this->projects();
        $this->table(['PROJECT ID', 'PROJECT NAME'], $this->projects);

        $this->preamble();
        $this->ready();
    }

    /**
     * Show the initial welcome message
     */
    protected function preamble()
    {
        $this->comment("Hey there, {$this->firstName}!");
        $this->comment('');
        $this->comment('Time can be entered in either of the following formats:');
        $this->comment('');
        $this->error('projectId|# of hours|description');
        $this->comment('');
        $this->comment('or');
        $this->comment('');
        $this->error('projectId|# of hours|description|date');
    }

    /**
     * Start the Camptime engine
     */
    protected function ready()
    {
        $timeEntry = $this->ask("🕐  I'm waiting patiently for a time entry...");

        $this->addTime($timeEntry);
    }

    /**
     * Add a time entry into Basecamp.
     * A time entry can be expressed as either:
     *     {projectId}|{hours}|{description}
     * or:
     *     {projectId}|{hours}|{description}|{date}
     *
     * If no date is provided, then today's date will be used.
     * 
     * @param string $entry
     */
    protected function addTime($entry)
    {
        $originalEntry = $entry;
        $entry = $this->decodeEntry($entry);
        $body = $this->entryXml($entry);

        $request = $this->client->post(
            "projects/{$entry['projectId']}/time_entries.xml", 
            $this->headers() + $this->body($body)
        );

        $this->readResult($originalEntry, $request);
    }

    /**
     * Get the result of the entry and restart the engine
     * 
     * @param  string $entry   The original time entry
     * @param  Request $reques
     */
    protected function readResult($entry, $request)
    {
        if ($request->getStatusCode() === 201)
        {
            $this->info("{$entry} added!");

            return $this->ready();
        }

        return $this->error("Now you've done it. Something didn't work right.💩");
    }

    /**
     * Build up an XML string for the time entry
     * 
     * @param  array $entry
     * @return string
     */
    protected function entryXml($entry)
    {
        return "<time-entry>
            <person-id>{$this->id}</person-id>
            <date>{$entry['date']}</date>
            <hours>{$entry['hours']}</hours>
            <description>{$entry['description']}</description>
        </time-entry>";
    }

    /**
     * Take the time entry and split it up into an array.
     * 
     * @param  string $entry The time entry
     * @return array
     */
    protected function decodeEntry($entry)
    {
        $entry = explode('|', $entry);

        return [
            'projectId' => $entry[0],
            'hours' => $entry[1],
            'description' => $entry[2],
            'date' => ! empty($entry[3]) ? $entry[3] : Carbon::now()->toDateString()
        ];        
    }

    /**
     * Get info from Basecamp about the current user
     */
    protected function me()
    {
        $request = $this->client->get('me.xml', $this->headers());
        $me = new SimpleXMLElement($request->getBody()->getContents());

        $this->id = json_decode(json_encode($me), TRUE)['id'];
        $this->firstName = json_decode(json_encode($me), TRUE)['first-name'];
    }

    /**
     * Get an alphabetized list of all Basecamp projects
     * 
     * @return array
     */
    protected function projects()
    {
        $request = $this->client->get('projects.xml', $this->headers());
        $xml = new SimpleXMLElement($request->getBody()->getContents());

        return $this->order(
            $xml->xpath("//project[status='active']")
        );
    }

    /**
     * Sort a SimpleXMLElement object by a specified column
     * 
     * @param  SimpleXMLElement $projects
     * @return array      [description]
     */
    protected function order($projects, $column = 'name')
    {
        return $this->reduce(array_values(
            array_sort(
                json_decode(json_encode($projects), TRUE), 
                function ($value) use($column) {
                    return $value[$column];
                }
            )
        ));
    }

    /**
     * Reduce the projects to just the project ID and name/client description
     * 
     * @param  array $projects
     * @return array
     */
    protected function reduce($projects)
    {
        foreach ($projects as $project) {
            $reduced[] = [
                'id' => $project['id'],
                'name' => sprintf('%s (%s)', $project['name'], $project['company']['name'])
            ];
        }

        return $reduced;
    }

    /**
     * Set the body for the request
     *
     * @return Array The Body
     */
    protected function body($body)
    {
        return ['body' => $body];
    }

    /**
     * Set the headers for the request
     *
     * @return Array The HTTP headers
     */
    protected function headers()
    {
        return [
            'headers' => [
                'Content-Type' => 'application/xml',
                'Accept' => 'application/xml',
                'User-Agent' => 'ET Camptime App',
            ],
            'auth' => [$this->apiKey, 'X']
        ];
    }
}
