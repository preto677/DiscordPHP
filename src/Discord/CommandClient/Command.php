<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\CommandClient;

use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;

class Command
{
    protected $command;
    protected $description;
    protected $usage;
    protected $subCommands       = [];
    protected $subCommandAliases = [];

    /**
     * Creates a command instance.
     *
     * @param DiscordCommandClient $client      The Discord Command Client.
     * @param string               $command     The command trigger.
     * @param \Callable            $callable    The callable function.
     * @param string               $description The description of the command.
     * @param string               $usage       The usage of the command.
     */
    public function __construct(
        DiscordCommandClient $client,
        $command,
        callable $callable,
        $description,
        $usage
    ) {
        $this->client      = $client;
        $this->command     = $command;
        $this->callable    = $callable;
        $this->description = $description;
        $this->usage       = $usage;
    }

    /**
     * Registers a new command.
     *
     * @param string           $command  The command name.
     * @param \Callable|string $callable The function called when the command is executed.
     * @param array            $options  An array of options.
     *
     * @return Command The command instance.
     */
    public function registerSubCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->subCommands)) {
            throw new \Exception("A sub-command with the name {$command} already exists.");
        }

        list($commandInstance, $options) = $this->client->buildCommand($command, $callable, $options);
        $this->subCommands[$command]     = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            $this->addSubCommandAlias($alias, $command);
        }

        return $commandInstance;
    }

    /**
     * Adds a sub-command alias.
     *
     * @param string $alias   The alias to add.
     * @param string $command The command.
     */
    public function addSubCommandAlias($alias, $command)
    {
        $this->subCommandAliases[$alias] = $command;
    }

    /**
     * Executes the command.
     *
     * @param Message $message The message.
     * @param array   $args    An array of arguments.
     *
     * @return mixed The response.
     */
    public function handle(Message $message, array $args)
    {
        $subCommand = array_shift($args);

        if (array_key_exists($subCommand, $this->subCommands)) {
            return $this->subCommands[$subCommand]->handle($message, $args);
        } elseif (array_key_exists($subCommand, $this->subCommandAliases)) {
            return $this->subCommands[$this->subCommandAliases[$subCommand]]->handle($message, $args);
        }

        if (! is_null($subCommand)) {
            array_unshift($args, $subCommand);
        }

        return call_user_func_array($this->callable, [$message, $args]);
    }

    /**
     * Handles dynamic get calls to the class.
     *
     * @param string $variable The variable to get.
     *
     * @return mixed The value.
     */
    public function __get($variable)
    {
        $allowed = ['command', 'description', 'usage'];

        if (array_search($variable, $allowed) !== false) {
            return $this->{$variable};
        }
    }
}