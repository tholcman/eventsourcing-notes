# Just a little note on CQRS and EventSourcing

CQRS and EventSourcing as I understand it

## The Command Part

### Command

Command should has some type and can carry some data, some payload.

```php
interface Command {
	// public function getType();
	public function getPayload();
};

class RegisterUser implements Command, Dispatchable {
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

```

We don't need to specify type, the class name bear this information. The name of the command should be imperative (DoSomething).

### Event

As Command, Event has a type, a payload and list of some kind of unique identifiers to know to which entities the event is relevant to.

```php
interface Event {
	// public function getType();
	public function getPayload();
	public function /* List<Id> */ getRelates();
};
```

Here is code of several entities with implementations. As you can see there is a lot of boilerplate code which can be refactored with some kind of generalization, e.g. to Traits, but for this simple example, we let the code in classes. Names of events should be in past tense (SomethingHappened).

```php
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
```

In Events code we use UserId which is just unique identifier of entity to which the event is relevant. Again, the code of UserId class is very general.

```php
interface Id {};

class UserId implements Id {
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
```

### CommandHandler

Commands are processed by CommandHandlers.

```php
interface CommandHandler {
	public function handle(Command $cmd);
};
```

In this example CommandHandler 'converts' the Command into several events and passed them to the EventDispatcher.

```php
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
```

For now there is no validation to preserve the code to be simple. Even if we want to add some validation, they would be just really simple rules for one value without context. More complex validation rules can be created in cooperation with a View on data. This View is created in The Query part and "The Query part" is not written yet :-) .


### Dispatcher

We pass Commands to Handlers trought Dispatcher. We use same dispatcher for both Commands and Events. Dispatcher can hold several Handlers and they may or may not react to Command/Event. Execution of `->handle($x)` method should be surrounded by try-catch.

```php
interface Dispatchable {};

interface Dispatcher{
	public function dispatch(Dispatchable $dsp);
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
```
### EventHandler

EventHandler is the last component of setup. Here is EventHandler which just prints incomming events. In real application I can imagine that there are several EventHandlers in one Dispatcher e.g. one for save to persistent DB, one for Views reprocessing e.t.c.


```php
interface EventHandler {
	public function handle(Event $cmd);
};


class PrintEventHandler implements EventHandler {
	public function handle(Event $event) {
		var_dump($event);
	}
}
```

Working example:

```php
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
```

The example php code is in this repo in `src/theCommandPart.php` so you can run it by

```
php src/theCommandPart.php
```

Maybe, I will write a Little note on the Query part. :-)

Sorry for bad grammar, I wrote this in just few tens of minutes, but I hope it is understandable - there is a lot of PHP code and I think, I am bit better in PHP language than in English :-)

