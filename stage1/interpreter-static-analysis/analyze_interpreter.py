# First Argument:  Table mapping php api to binary address. Use the extension to get this
# Second Argument: objdump of php binary
# Third Argument: objdump of libc

import sys
import os
import re
import subprocess
from subprocess import Popen, PIPE, STDOUT


labels = dict()
php_sources = dict()

ctxs=dict()

cached = dict()

ignore=["lea", "mov", "cmpq", "movl", "cmpb", "pushq", "movq", "test", "cmpl",
"movb",
"movdqa",
"cmp",
"movdqu",
"movsd",
"movslq",
"movswq",
"movups",
"movzbl",
"movzwl",
"mulsd",
"orl",
"sub",
"subl",
"subsd",
"testb",
"ucomisd",
"xor",
"xorpd",
"cmove",
"cvtsi2s",
"divsd",
"cmovne",
"add",
"addsd",
"andpd"]

libaliases = [f for f in os.listdir("libs/") if os.path.isfile(os.path.join("libs/", f))]

colors = ['\033[95m',
'\033[94m',
'\033[92m',
'\033[93m',
'\033[91m',
'\033[1m',
'\033[4m',
'\033[0m',
]

loglevel = 5


def log(m, level):
    if level >= loglevel:
        print(m)



binds = dict()

class ctx:
    graph = set()
    sym2addr = {}
    addr2sym = {}
    addr2syscall = {}
    offsets = {}
    def __init__(self):
        self.graph = set()
        self.sym2addr = {}
        self.addr2sym = {}
        self.addr2syscall = {}

def ldd(f):
    files = []
    out = os.popen('ldd '+ f).read()
    for l in out.splitlines():
        l = l.replace("=>",'^')
        l = l.split('^')
        if len(l) > 1:
            files.append(l[1].split()[0])
    return files 
def readelf(f, c):
    out = os.popen('readelf -s '+f).read()
    for line in out.splitlines():
        line = line.split()
        if len(line) !=8 or line[0]=="Num:":
            continue
        addr = int(line[1], 16)
        if addr != 0:
            addsym(c, line[7], addr)

def addsym(g, sym, addr):
    if "+"in sym:
        name = sym.split('+')[0]
        g.offsets[addr]=sym
    if addr not in g.addr2sym:
        g.addr2sym[addr]=set()
    g.addr2sym[addr].add(sym)
    if sym not in g.sym2addr:
        g.sym2addr[sym]=addr

def bindings(f):
    dump = f.splitlines()
    for line in dump:
        if "binding file" not in line:
            continue
        result = 0
        result = re.search('\] to (.*) \[.*\]:.*`(.*)\'\s*\[(.*)\]', line)
        if result:
            origin = result.group(1)
            sym = result.group(2)
            loc = result.group(3)
        else:
            result = re.search('\] to (.*) \[.*\]:.*`(.*)\'', line)
            origin = result.group(1)
            sym = result.group(2)
            loc = ""
        binds[(sym, loc)] =  origin



def cfg(f, subg):
    g = ctx()
    func_addr = ""
    func_name =""

    dump = f.splitlines()
    lines = []

    count = 1
    while count !=0 :
        count = 0
        for line in dump:
            lines.append(line)
            if '<' in line and line.rstrip()[-1] == ':':
                line = line.split()
                func_addr = int(line[0], 16)
                func_name = line[1]
                result = re.search('<(.*)>', func_name)
                func_name = result.group(1)
                addsym(g, func_name, func_addr)

            else:
                # Check for syscalls 
                if "%eax" in line or "%rax" in line:
                    eax = line
                elif len(line.split())>0:
                    if (line.split())[-1]=="syscall":
                        if "syscall" in func_name:
                            pass
                        else:
                            if func_addr not in g.addr2syscall:
                                g.addr2syscall[func_addr]=set()
                            src=0
                            if "xor    %eax,%eax" in eax:
                                src = "$0"
                            else:
                                result = re.search('mov\s*(.*),.*', eax)
                                if result != None:
                                    src=result.group(1)
                                else:
                                    src='-1'
                            if src[0]=="%" or src[0:2]=="(%":
                                if src[0] == "(":
                                    src = src[1:len(src)-1]
                                i=0
                                while(True):
                                    i-=1
                                    if "xor    "+src+","+src in lines[i]:
                                        g.addr2syscall[func_addr].add(0)
                                        break
                                    result = re.search('mov\s*(.*),'+src, lines[i])
                                    if result != None:
                                        try:
                                            res = int(result.group(1)[1:], 16)
                                            g.addr2syscall[func_addr].add(res)
                                        except:
                                            pass
                                        break
                            elif src != '-1':
                                g.addr2syscall[func_addr].add(int(src[1:], 16))

                # Check for calls
                if '<' in line:
                    if subg != None and func_addr not in subg:
                        continue
                    cont = 1
                    for w in line.split():
                        if w in ignore:
                            cont = 0
                    if cont == 0:
                        continue
                    line_addr = int(line.split(":")[0], 16)
                    result = re.search('([a-f,0-9]*\ <.*>)', line)
                    line = result.group(1)
                    line = line.split()
                    addr = int(line[0], 16)
                    name = line[1]
                    result = re.search('<(.*)>', name)
                    name = result.group(1)
                    if func_name!=name:
                        skip = False
                        if len(name) >= len(func_name):
                            if name[0:len(func_name)]==func_name:
                                if func_name+'+' in name:
                                    skip = True
                        if ".plt.got" in func_name:
                            g.graph.add((line_addr, addr))
                            addsym(g, name, addr)
                        elif not skip and (func_addr, addr) not in g.graph:
                            if subg != None:
                                subg.add(addr)
                            g.graph.add((func_addr, addr))
                            addsym(g, name, addr)
                            count+=1
    return g


def resolve_offsets(g):
    skip = "_init@"
    for o, sym in g.offsets.items():
        if sym[0:len(skip)] == skip:
            continue
        addr = 0
        shortsym = sym.split('+')[0]
        if shortsym in g.sym2addr:
            addr = g.sym2addr[shortsym]
            log("Resolved " + sym + " to " + str(g.addr2sym[addr]), 0)
            g.graph.add((o,addr))


def load_php_api(f):
    subg = set()
    classname = None
    with open(f, 'r') as fp:
        line = fp.readline()
        while line:
            line = line.split()
            if line[0] == 'CLASS':
                classname = line[1]
            else:
                addr = int(line[1], 16)
                if addr not in php_sources:
                    php_sources[addr] = set()
                if classname:
                    php_sources[addr].add(classname+"::"+line[0])
                else:
                    php_sources[addr].add(line[0])
                subg.add(addr)
            line = fp.readline()
    return subg


def sym_name(ctx, addr):
    if addr in ctx.addr2sym:
        for i in ctx.addr2sym[addr]:
            return i
    else:
        return str(hex(addr))

def dfs(path, ctxs, done):
    source = path[-1][0]
    f = path[-1][1]
    if path[-1] in cached:
        log("cached: " + sym_name(ctxs[f],source) + " = " + str(cached[path[-1]]), 0)
        return cached[path[-1]]

    # Check whether there are any syscalls in latest node
    syscalls = set()
    # Check for neightbors within the same binary
    count = 0   # How many new edges within the same binary
    for edge in ctxs[f].graph:
        if edge[0] == source and (edge[1], f) not in path and(path[-1], (edge[1], f)) not in done :
            count += 1
            done.add((path[-1], (edge[1], f)))
            ss = dfs(path + [(edge[1], f)], ctxs, done)
            log("\""+sym_name(ctxs[f],source)+"@"+f+"\" -> \""+ sym_name(ctxs[f],edge[1])+"@"+f+"\"", 2)
            syscalls |= ss

    # Check for neightbors within other binaries
    if count == 0:
        if source in ctxs[f].addr2sym:
            sym = ""
            syms = ctxs[f].addr2sym[source]
            ss = ""
            for s in syms:
                ss = s
                s = s.split('@')
                if len(s)>1:
                    s=(s[0], s[-1])
                else:
                    s=(s[0], "")
                if s in binds:
                    sym = s
                    break

            if sym not in binds:
                pass
            else:
                ff = binds[sym]
                if ff in ctxs:
                    c = ctxs[ff]
                    for sym in ctxs[f].addr2sym[source]:
                        for s in [sym, sym.replace('@', '@@')]:
                            if s in c.sym2addr:
                                caddr = c.sym2addr[s]
                                if (caddr, ff) not in path and (path[-1], (caddr, ff)) not in done:
                                    done.add((path[-1], (caddr, ff)))
                                    ss = dfs(path + [(caddr, ff)], ctxs, done)
                                    log("\""+sym_name(ctxs[f],source)+"@"+f+"\" -> \""+ sym_name(c,caddr)+"@"+ff+"\"", 2)
                                    syscalls |= ss
                                
    if source in ctxs[f].addr2syscall:
        syscalls |= ctxs[f].addr2syscall[source]
    syscalls.discard(59)
    log("For " + sym_name(ctxs[f],source) + " caching: " + str(syscalls), 1)
    cached[path[-1]] = syscalls
    return syscalls



def main():
    subg = load_php_api(sys.argv[1])

    php_bin = sys.argv[2]
    libs = ldd(php_bin)

    # Bindings
    output = Popen("LD_DEBUG=bindings " + php_bin +  " /dev/null", shell=True, stdout=PIPE, close_fds=True, stderr=STDOUT).stdout.read().decode("utf-8") 
    bindings(output)

    output = os.popen('objdump -d '+sys.argv[2]).read()
    ctxs[php_bin]=cfg(output, None)
    resolve_offsets(ctxs[php_bin])
    for l in libs:
        lalias = l
        if os.path.basename(l) in libaliases:
            lalias = "./libs/" + os.path.basename(l)
        log(l, 1)
        output = os.popen('objdump -d '+lalias).read()
        ctxs[l]=cfg(output, None)
        resolve_offsets(ctxs[l])
        log(ctxs[l].addr2syscall, 1)
        readelf(l, ctxs[l])

    for n in php_sources:
        log("================= " + str(php_sources[n]) +" ==================", 1)
        origins = set()
        for alias in php_sources[n]:
            if "zif_"+alias  in ctxs[php_bin].sym2addr:
                origins.add((ctxs[php_bin].sym2addr["zif_"+alias], php_bin))
            if "php_if_"+alias  in ctxs[php_bin].sym2addr:
                origins.add((ctxs[php_bin].sym2addr["php_if_"+alias], php_bin))
        origins.add((n, php_bin))
        scs = dfs(list(origins), ctxs, set([]))
        for s in php_sources[n]:
            log("%50s: %s"%(s, " ".join([str(i) for i in sorted(scs)])), 5)


if __name__ == "__main__":
    main()


