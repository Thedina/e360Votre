# EProcess v3 **** INCOMPLETE ignore this for now I'm improving and testing some stuff first instead of writing about the old version ****

## Setting up a new portal

Since this is something people have notably had problems with and multiple people have recently joined the team, I've
decided to start writing down current procedure for setting up a new portal instance from scratch, which I will update 
as it changes. As you will see there are a couple of very narrow requirements still which will *definitely* be changing,
but just in case somebody has reason to do this while I'm not around...

And it will be good to figure out more ways that the process can be broken, and for me to take an inventory of what
features should be added.

**The procedure here is only accessible to people who have permissions on the slcodev-eprocess-360.com Google Cloud 
project.** Anybody who is not who needs a development server should probably let someone who can set up it for them 
right now. We will figure out the best way to handle Google API access for many portals in the future.

1. In a web browser, go to console.developers.google.com (while logged in, naturally).

2. Navigate to Compute Engine->VM Instances

3. Find the "v25-setuptest" VM. Click on its name.

4. On the info page for this server click "Clone" at the top. Click "Create" and proceed through the VM creation prompt.
