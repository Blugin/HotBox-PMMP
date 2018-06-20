# <img src="./assets/icon/index.svg" height="50" width="50"> HotBox
__A plugin for [PMMP](https://pmmp.io) :: Have fun with the box for hot-time!__

[![license](https://img.shields.io/github/license/PresentKim/HotBox-PMMP.svg?label=License)](LICENSE)
[![release](https://img.shields.io/github/release/PresentKim/HotBox-PMMP.svg?label=Release)](https://github.com/PresentKim/HotBox-PMMP/releases/latest)
[![download](https://img.shields.io/github/downloads/PresentKim/HotBox-PMMP/total.svg?label=Download)](https://github.com/PresentKim/HotBox-PMMP/releases/latest)

## What is this? 
Hot box (means hot-time reward box) is a plugin that make easily give for hot-time rewards.  
You can easily set reward like a chest by opening the reward setting box.  
Enable hot-time allows users to open hotbox.  
  
## Features
- [x] Save plugin data in NBT format (`Reward items`, `Whether to is hot time`, `Last hot-time`, etc.))
- [x] OP can edit hot-time reward
  - [x] Use the chest to make reward edit easier  
- [x] User can open hot box
  - [x] Hot box can only be opened when hot-time is enabled  
  - [x] Prevent put item in the hot box (Prevent use the hot box like to `virtual chest`)  
  - [x] Save hot box data to each player's NBT data  
  - [x] Reset hot box data of player when enable hot-time  
- [x] Handle execute command without arguments  
  - [x] OP)   Open menu form for select sub command  
  - [x] USER) Open hot box  
- [ ] Hot-time automation
  - [ ] Set hot-time period  
  - [ ] Automatically start at specific times  
- [ ] Add a setting that allows to open the box only once  


## Configurable things
- [x] Configure the language for messages
  - [x] in `{SELECTED LANG}/lang.ini` file  
  - [x] Select language in `config.yml` file
- [x] Configure the command (include subcommands)
  - [x] in `config.yml` file
- [x] Configure the form id (for prevent duplicate collisions with other plugin)
  - [x] in `config.yml` file
- [ ] Configure the permission of command  
  - [ ] in `config.yml` file

The configuration files is created when the plugin is enabled.  
The configuration files is loaded  when the plugin is enabled.  


## Command
Main command : `/hotbox [Open | Edit | On | Off]`

| subcommand | arguments | description               |
| :--------- | :-------- | :------------------------ |
| Open       |           | Open hot box (reward box) |
| Edit       |           | Edit hot-time reward      |
| On         |           | Enable hot-time           |
| Off        |           | Disable hot-time          |



## Permission
| permission        | default | description       |
| :---------------- | :-----: | ----------------: |
| hotbox.cmd        |  USER   |      main command |
|                   |         |                   |
| hotbox.cmd.open   |  USER   |   open subcommand |
| hotbox.cmd.edit   |   OP    |   edit subcommand |
| hotbox.cmd.on     |   OP    |     on subcommand |
| hotbox.cmd.off    |   OP    |    off subcommand |
