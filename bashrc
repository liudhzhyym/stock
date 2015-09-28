
LPATH="$HOME/opdir/backup"
DATETIME=$(date +%Y%m%d%H%M%S)

#mkdir and cd if success
function lmk () { 
	mkdir -p "$@" && eval cd "\"\$$#\""; 
}


make_ps(){
    host=`hostname | sed -e "s/.baidu.com//"`
    PS1="\[\e[00;48m\]\u@\[\e[01;40;33m\]${host}\[\e[40;31m\]:\w\[\e[40;31m\]/\[\e[0;0m\] "
}


#减少目录冲突的询问
function op() {
	local DATE=$(date +%Y%m%d%H)
	if [ -z $1 ]
	then
		lmk ~/opbin/$DATE
		ls -l
	else
		lmk ~/opbin/$DATE"$1"
		ls -l
	fi
}

function opmv(){
	local DATE=$(date +%Y%m%d%H)
	local SECOND=$(date +%I%S)
    opmvPath="${HOME}/opdir/backup/opmv.${DATE}/${SECOND}/"
    mkdir -p $opmvPath
    mv "$@" $opmvPath
    if [ $? -eq 0 ] ; then
        echo -e "\033[32mmv [$@] to [$opmvPath] done\033[0m"
    else
        echo -e "\033[31mmv [$@] to [$opmvPath] failed\033[0m"
    fi
}

#full path of the given file
function fp() {
	echo `pwd`/$1
}

function np() {
	echo `hostname`:`pwd`/$1
}


#$1 timestamp . 
#timestamps are the seconds after 1990-1-1
function ctime() {
	date -d "UTC 1970-01-01 $1 secs"
}

function now() {
	echo $(date +%Y%m%d%H%M%S)
}

#get pid from supervise status file
function pid() {
	od -An -j16 -N2 -tu2 $1
}


##########networking#######################
#lnc: ldh network connection
#list all the host which is connected to this host
function lnc() {
	netstat -tualp 2> /dev/null | awk '{print $5}' | awk -F: '/:/{print $1}' | sort -rn | uniq 
}

function lnet()
{
	netstat -tualp | grep $1
}

##########system#######################

function lps()
{
	ps -eLf | grep -v grep | grep $1
}


function ac()
{
    grep "\" ${1}[0-9][0-9] " access_log | cut -d' ' -f 4 | uniq -c
}


alias ls='ls --color=auto'
alias ll='ls --color=auto -al'
alias lwh='watch -d -n 1 head'
alias screenn='screen -S ldh'
alias dug='du -sh * | grep G'
alias dulog='du -sh */* | grep log | grep G'
alias listlog="find . -mtime +1 | awk '{print $1}'"
alias grep="grep --color"
alias vir="vim -R"
alias tf="tail -f"
alias hour="date +%Y%m%d%H"
alias sudoiu="sudo -s -H -u"
alias e='exit'
alias gp='git push -u origin master'
alias gs='git status .'
alias gc='git commit . -m "update"'

make_ps




