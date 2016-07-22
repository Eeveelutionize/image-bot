To set up, copy `config.sample.php` to `config.inc.php` and edit with bot token.

Use `pm2` to maintain the bot. A command would look something like

```
pm2 start bot.php --name discord-bot -e err.log -o out.log
```

