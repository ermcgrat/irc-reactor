I'm lazy and needed to modify the Jerk package just a tiny bit. I installed it (npm install --save jerk), and then just went into node_modules and modified its code.

Basically, instead of just getting ahold of the user's nick, we want their hostname as well. In the _make_message function, we change the return from:

user: message.person.nick
to
user: message.person

The user (person) object will now contain nick, user (username), and host (hostmask)