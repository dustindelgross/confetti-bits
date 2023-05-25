# Welcome to **Confetti Bits**.

This is our culture gamification system! :D

We use this to send and receive *Confetti Bits* to each other, and earn them based on culture participation. We're currently on version 2.3.0, and working slowly toward a new update that will automate about 95% of all transactions. Super exciting!

## Installation

To install this plugin, you'll currently need to have BuddyBoss also installed ([more info here](https://www.buddyboss.com/) ). I'm working to remove that ~~incredibly beefy and expensive~~ dependency soon, but I have yet to make it happen. _I'm sweating._

Anyways, you would install this by downloading a zip from this repo and installing it directly into your WordPress instance.

## Key Components

As of right now, we have a few different components working together to make the magic happen. Here's a little list:

- The Confetti Bits Class
    - This autoloads all of the rest of our classes, registers a few globals, and starts up the core component.
- Core
    - Our core component handles loading all our component classes so we can access their globals from anywhere.
- Transactions
    - Our first "this-does-stuff" component! Handles sending and receiving bits.
- Participation
    - Handles submitting and managing participation entries.
- Events
    - Handles event creation and point value association. 
	- Includes the Contests class, which registers contest placements and ties them to event entries.
- Notifications
    - You guessed it - handles sending and receiving notifications based on user actions!
- Templates
    - Turns our UI into a pain-free, blissful endeavor.
- AJAX
    - Mind not the outdated name, this is what handles our REST API shenanigans so we can play nice with Javascript.

We have a leaderboard, a few data tables, a running balance calculation, some transactional stuff going on, and some moderation features, among others. It's a pretty nifty thing, with much more to come. 