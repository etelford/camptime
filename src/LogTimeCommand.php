<?php

namespace Camptime;

use Carbon\Carbon;
use SimpleXMLElement;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LogTimeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logtime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log time to your Basecamp using Camptime';

    public function __construct()
    {
        $this->client = new Client(['base_uri' => getenv('BASCAMP_BASE_URI')]);
        $this->apiKey = getenv('BASECAMP_API_KEY');

        parent::__construct();
    }

    /**
     * Configure the command for run time.
     *
     * @return void
     */
    public function configure()
    {
        $this->setName('logtime')
            ->setDescription('Logs time to basecamp');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->myBaseCamp();
        $this->projects = $this->projects();
        $this->output->table(['PROJECT ID', 'PROJECT NAME'], $this->projects);

        $this->preamble();
        $this->ready();
    }

    /**
     * Start the Camptime engine
     */
    protected function ready()
    {
        $timeEntry = $this->output->ask("🕐  I'm waiting patiently for a time entry...");

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
     * Show the initial welcome message
     */
    protected function preamble()
    {
        $this->output->writeLn("Hey there, {$this->firstName}!");
        $this->output->writeLn('Time can be entered in either of the following formats:');
        $this->output->section('projectId|# of hours|description');
        $this->output->writeLn('or');
        $this->output->section('projectId|# of hours|description|date');
    }

    /**
     * Get the result of the entry and restart the engine
     *
     * @param  string $entry   The original time entry
     * @param  Request $reques
     */
    protected function readResult($entry, $request)
    {
        if ($request->getStatusCode() === 201) {
            $this->output->text("{$entry} added!");

            return $this->ready();
        }

        return $this->output->error("Now you've done it. Something didn't work right.💩");
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
    protected function myBaseCamp()
    {
        $request = $this->client->get('me.xml', $this->headers());
        $myBaseCamp = new SimpleXMLElement($request->getBody()->getContents());

        $this->id = json_decode(json_encode($myBaseCamp), true)['id'];
        $this->firstName = json_decode(json_encode($myBaseCamp), true)['first-name'];
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
                json_decode(json_encode($projects), true),
                function ($value) use ($column) {
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
