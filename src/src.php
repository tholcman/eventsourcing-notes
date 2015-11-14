<?php
interface Id {};

interface Command {
	// public function getType();
	public function getPayload();
};

interface Event {
	// public function getType();
	public function getPayload();
	public function /* List<Id> */ getRelates();
};

interface Dispatchable {};

interface Dispatcher{
	public function dispatch(Dispatchable $dsp);
};

interface EventHandler{
	public function handle(Event $cmd);
};
interface CommandHandler{
	public function handle(Command $cmd);
};

class CommonDispatcher implements Dispatcher {
	private $handlers;
	public function __construct($handlers) {
		$this->handlers = $handlers;
	}
	public function dispatch(Dispatchable $dsp) {
		foreach ($this->handlers as $handler) {
			$handler->handle($dsp);
		}
	}
}

class RegisterUser implements Command, Dispatchable{
	private $payload;
	public function __construct($name, $email) {
		$this->payload = [
			'name' => $name,
			'email' => $email
		];
	}
	public function getPayload() {
		return $this->payload;
	}
}

class UserId {
	private $id;
	public static function create() {
		return new UserId(uniqid());
	}
	public function __construct($id) {
		$this->id = $id;
	}
	public function getId() {
		return $this->id;
	}
}

class NameSet implements Event, Dispatchable{
	private $payload, $relates;
	public function __construct(UserId $id, $name) {
		$this->payload = $name;
		$this->relates = [
			$id
		];
	}
	public function getPayload() {
		return $this->payload;
	}
	public function getRelates() {
		return $this->relates;
	}
}
class EmailSet implements Event, Dispatchable {
	private $payload, $relates;
	public function __construct(UserId $id, $email) {
		$this->payload = $email;
		$this->relates = [
			$id
		];
	}
	public function getPayload() {
		return $this->payload;
	}
	public function getRelates() {
		return $this->relates;
	}
}

class UserRegistrationHandler implements CommandHandler {
	private $eventDispatcher;
	public function __construct($eventDispatcher) {
		$this->eventDispatcher = $eventDispatcher;
	}
	public function handle(Command $cmd) {
		if (!($cmd instanceof RegisterUser)) {
			return;
		}
		$id = UserId::create();
		$user = $cmd->getPayload();
		$nameSet = new NameSet($id, $user['name']);
		$emailSet = new EmailSet($id, $user['email']);
		// name and email events validation
		$this->eventDispatcher->dispatch($nameSet);
		$this->eventDispatcher->dispatch($emailSet);
	}
}

class PrintEventHandler implements EventHandler {
	public function handle(Event $event) {
		var_dump($event);
	}
}


$eventHandler = new PrintEventHandler;

$eventDispatcher = new CommonDispatcher([
	$eventHandler
]);

$registerHandler = new UserRegistrationHandler($eventDispatcher);

$commandDispatcher = new CommonDispatcher([
	$registerHandler
]);

$registerUser = new RegisterUser(
	"Tomáš Holcman",
	"tomas.holcman@gmail.com"
);

$commandDispatcher->dispatch($registerUser);
