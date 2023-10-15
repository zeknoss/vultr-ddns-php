# VULTR DNS with PHP
Utilize Vultr DNS and Vultr API to create yourself a free DDNS!

You can use this single file (along with a config file) to easily use Vultr API and create yourself a DDNS solution for your homelab.

## How does it work?
1. Have an account on Vultr
2. Obtain an API key from https://my.vultr.com/settings/#settingsapi
3. Create a DNS domain in `Products` > `Network` > `DNS`
4. Create A records (i.e. `local.mydomain.com`, `dev.mydomain.com`, `mymediaserver.mydomain.com`, etc)
5. Clone this repo in your homelab
6. Update the vultr.config.json file with your settings. `dynamic_records` section should consist of only A record names. I.e. `["local", "dev", "myymediaserver"]`
7. Create a cron job with a reasonable interval as follows: `cd /path/to/repo/directory/ && php VultrDNS.php` (I used every 30 minutes throughout everyday so it will check for changes and update the record accordingly)

By the way, big shout outs to @andyjsmith for inspiring me into making this project with his awesome https://github.com/andyjsmith/Vultr-Dynamic-DNS repo!

I know the script is very very crude. So all contributions are welcome :) All hands on deck!
