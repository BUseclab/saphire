DIR=$(shell pwd)
GOBIN=$(shell pwd)/build
#GOPATH=$(shell pwd)/vendor:$(shell pwd)
GOFILES=$(wildcard *.go)
GONAME=$(shell basename "$(PWD)")

all: $(GOBIN)/scan-project $(GOBIN)/register-functions $(GOBIN)/register-syscalls $(GOBIN)/process-traces $(GOBIN)/build-filters

$(GOBIN)/scan-project:
	cd $(DIR)/`basename $@` ; go build -o $@ 

$(GOBIN)/register-functions:
	cd $(DIR)/`basename $@` ; go build -o $@ 

$(GOBIN)/register-syscalls:
	cd $(DIR)/`basename $@` ; go build -o $@ 

$(GOBIN)/process-traces:
	cd $(DIR)/`basename $@` ; go build -o $@ 

$(GOBIN)/build-filters:
	cd $(DIR)/`basename $@` ; go build -o $@ 

clean:
	rm -rf $(GOBIN)/*
