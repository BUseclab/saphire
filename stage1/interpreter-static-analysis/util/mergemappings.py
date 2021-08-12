import os
import sys


map1 = sys.argv[1]
map2 = sys.argv[2]


def readmapping(f):
    mapping = dict()
    with open(f, 'r') as fd:
        for line in fd:
            line = line.split()
            if len(line) == 0:
                continue
            fn = line[0]
            mapping[fn] = set()
            for sc in line[1:]:
                if sc != "+++" and sc != "---":
                    mapping[fn].add(sc)
    mapping.pop("system:", None)
    mapping.pop("proc_open:", None)
    mapping.pop("popen:", None)
    mapping.pop("shell_exec:", None)
    mapping.pop("pcntl_exec:", None)
    mapping.pop("exec:", None)
    mapping.pop("passthru:", None)
    return mapping

map1dict = readmapping(map1)
map2dict = readmapping(map2)

# remove unsupported funcs

for k in map1dict:
    if k not in map2dict:
        print("{} {}".format(k, " ".join(map1dict[k])))
for k in map2dict:
    if k in map1dict:
        print("{} {}".format(k, " ".join(map1dict[k]|map2dict[k])))
    #else:
        #print("{}: {}".format(k, " ".join(map2dict[k])))

# print("In " + map1 + " not " + map2)
# print("============================")

def intersection(): 
    for k, v in map1dict.items():
        if k not in map2dict:
            continue
        notin1 = map2dict[k]-v
        if notin1 == set(['brk']):
            continue
        if len(notin1) > 0:
            print(k + " " + " ".join(notin1))

    # for k, v in map2dict.items():
    #     if k not in map1dict:
    #         print("Skipping: " + k )
    #         continue

def plot1(): 
    for k, v in map1dict.items():
        intersection = 0
        notin2 = len(v)
        notin1 = 0
        if k not in map2dict:
            continue
        intersection = len(v.intersection(map2dict[k]))
        notin2 = len(v-map2dict[k])
        notin1 = len(map2dict[k]-v)
        total = str(intersection + notin1 + notin2)
        # difference = v - map2dict[k]
        print(str(total) + " " + k + " " + str(intersection) + " " + str(notin2) + " " + str(notin1))
        # if len(difference) > 0:
        #     if difference != set(["brk"]):
        #         print(k + " " + " ".join(difference))

    # for k, v in map2dict.items():
    #     if k not in map1dict:
    #         print("Skipping: " + k )
    #         continue

def plot2():
    for k, v in map1dict.items():
        intersection = 0
        notin2 = len(v)
        notin1 = 0
        v-=set(["brk"])
        if k not in map2dict:
            continue
        map2dict[k]-=set(["brk"])
        intersection = len(v.intersection(map2dict[k]))
        notin2 = len(v-map2dict[k])
        notin1 = len(map2dict[k]-v)
        total = str(intersection + notin1 + notin2)
        if notin1 != 0:
            print(str(total) + " " + k + " " + str(intersection) + " " + str(notin2) + " " + str(notin1))

#plot1()
#intersection()

