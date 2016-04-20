#include <stdio.h>
#include <sys/types.h>
#include <unistd.h>
#include <stdlib.h>

/*  Wrapper for launching SSL install helper scripts as setuid root.
    Passes shell args as-is, with an empty environment.  */
int main(int argc, char **argv) {
    int result;

    if(argc < 2) {
        exit(-1);
    }

    setuid(0);

    if(!strcmp(argv[1], "check-cert-installed")) {
        result = execve("/home/master-epro/ssl/helper/check_cert_installed.py", argv, NULL);
    }
    else if(!strcmp(argv[1], "install-cert")) {
        if(argc < 3) {
            exit(-1);
        }
        else {
            result = execve("/home/master-epro/ssl/helper/install_cert.py", argv, NULL);
        }
    }
    else {
        exit(-1);
    }

    return result;
}
