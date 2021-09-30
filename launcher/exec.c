#include <windows.h>
#include <stdio.h>
#include <tchar.h>
#include <unistd.h>
#include <string.h>

#define BUFSIZE MAX_PATH

int _tmain(int argc, TCHAR **argv){
  TCHAR buffer[BUFSIZE+20];
  DWORD dwRet;
  int len;

  dwRet = GetCurrentDirectory(BUFSIZE, buffer);

  if( dwRet == 0 ) {
    printf("GetCurrentDirectory failed (%d)\n", GetLastError());
    return 1;
  }
  if(dwRet > BUFSIZE){
    printf("Buffer too small; need %d characters\n", dwRet);
    return 2;
  }
  strcpy(buffer+dwRet, "\\password-tool");

  SetCurrentDirectory(buffer);
//  execl("D:/test/hello","D:/test/hello",0);

  UINT res =  WinExec(
    "password-tool.exe",
    SW_SHOW
  );
  return 0;
}