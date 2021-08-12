import os
import sys
import re

table = "/usr/include/x86_64-linux-gnu/asm/unistd_64.h"

if len(sys.argv) > 2:
    table = sys.argv[2]

with open(sys.argv[1], 'r') as f:
    whitelists = f.read()

with open(table, 'r') as f:
    for line in f:
        line = line.rstrip().split(" ")
        if len(line) != 3:
            continue
        if line[0] != "#define" or "_NR" not in line[1]:
            continue
        name = line[1][5:]
        nr = line[2]
        whitelists = re.sub(r"\b%s\b" % nr, name, whitelists)
print(whitelists)

