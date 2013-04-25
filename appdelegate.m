#import "Popups+DSTBlocks.h"
#import "PLCrashReporter.h"
#import "PLCrashReport.h"

// HTTP update server basename
#define HTTP_UPDATE_URL @"http://app.example.com/"
#define APP_NAME @"testapp"

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions {
    PLCrashReporter *crashReporter = [PLCrashReporter sharedReporter];
    NSError *error;

    // Check if we previously crashed
    if ([crashReporter hasPendingCrashReport]) {
        [self handleCrashReport];
    }

    // Enable the Crash Reporter
    if (![crashReporter enableCrashReporterAndReturnError: &error]) {
        NSLog(@"Warning: Could not enable crash reporter: %@", error);
        error = nil;
    } else {
        NSLog(@"Crash reporting enabled");
    }

    // check if there is an update available
#if !TARGET_IPHONE_SIMULATOR
    dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_BACKGROUND, 0), ^{
        dispatch_async(dispatch_get_main_queue(), ^{
            [self updateCheck];
        });
    });
#else
    NSLog(@"running on simulator, not performing update check");
#endif

    /*******************************
     *** Your code here
     *******************************/
}

#pragma mark - Update check
- (void)updateCheck {
    if (![[Reachability reachabilityForLocalWiFi] isReachable]) {
        NSLog(@"no internet connection via WiFi, cancelling check");
        return;
    }

    NSError *error = nil;
    NSURL *url = [NSURL URLWithString:[NSString stringWithFormat:@"%@version.php", HTTP_UPDATE_URL]];
    NSMutableURLRequest *request = [[NSMutableURLRequest alloc] initWithURL:url];
#ifdef HTTP_UPDATE_PASSWORD
    NSString *authStr = [NSString stringWithFormat:@"%@:%@", HTTP_UPDATE_USERNAME, HTTP_UPDATE_PASSWORD];
    NSString *authValue = [NSString stringWithFormat:@"Basic %@", [authStr base64EncodedString]];
    [request setValue:authValue forHTTPHeaderField:@"Authorization"];
#endif
    NSURLResponse *response = nil;
    NSData *result = [NSURLConnection sendSynchronousRequest:request returningResponse:&response error:&error];
    if (error) {
        NSLog(@"Error while querying update server: %@", error);
        return;
    }
    NSDictionary *versionData = [NSJSONSerialization JSONObjectWithData:result options:0 error:&error][APP_NAME];
    onlineVersion = versionData[@"current"];

    NSString *currentVersion = [[[NSBundle mainBundle] infoDictionary] objectForKey:@"CFBundleVersion"];
    if (![currentVersion isEqualToString:onlineVersion]) {
        NSLog(@"new version is available: %@", onlineVersion);
        dispatch_async(dispatch_get_main_queue(), ^{
            DSTBlockButton *okButton = [DSTBlockButton buttonWithTitle:NSLocalizedString(@"Update", @"update button autoupdater dialog")
                                                                 block:^{
                                                                     NSURL *url = [NSURL URLWithString:[NSString stringWithFormat:@"itms-services://?action=download-manifest&url=%@",  versionData[@"url"]]];
                                                                     [[UIApplication sharedApplication] openURL:url];
                                                                     NSLog(@"user started update to %@", onlineVersion);
                                                                     [[UIApplication sharedApplication] openURL:url];
                                                                 }];
            DSTBlockButton *cancelButton = [DSTBlockButton buttonWithTitle:NSLocalizedString(@"Cancel", @"cancel button autoupdater dialog")
                                                                     block:^{
                                                                         NSLog(@"user cancelled update");
                                                                     }];

            UIAlertView *alert = [[UIAlertView alloc] initWithTitle:NSLocalizedString(@"App update detected", @"title autoupdater dialog")
                                                            message:[NSString stringWithFormat:NSLocalizedString(@"There is a new Version (%@) on the server. (you have %@)", @"prompt autoupdater dialog"), onlineVersion, currentVersion]
                                                       cancelButton:cancelButton
                                                       otherButtons:@[ okButton ]];
            [alert show];
        });
    } else {
        NSLog(@"using most current version");
    }
}


#pragma mark - Crash reporting
- (void) handleCrashReport {
    PLCrashReporter *crashReporter = [PLCrashReporter sharedReporter];
    NSData *crashData;
    NSError *error;

    // Try loading the crash report
    crashData = [crashReporter loadPendingCrashReportDataAndReturnError: &error];
    if (crashData == nil) {
        NSLog(@"Could not load crash report: %@", error);
        [crashReporter purgePendingCrashReport];
        return;
    }

    NSUserDefaults *prefs = [NSUserDefaults standardUserDefaults];
    if ([prefs boolForKey:@"sendCrashReports"]) {
        [self sendCrash:crashData];
    } else {
        DSTBlockButton *cancelButton = [DSTBlockButton buttonWithTitle:NSLocalizedString(@"Do not send", @"cancel button crashlog dialog")
                                                                 block:^{
                                                                     [crashReporter purgePendingCrashReport];
                                                                 }];
        DSTBlockButton *send         = [DSTBlockButton buttonWithTitle:NSLocalizedString(@"Send crashlog", @"send button crashlog dialog")
                                                                 block:^{
                                                                     [self sendCrash:crashData];
                                                                 }];
        DSTBlockButton *sendalways   = [DSTBlockButton buttonWithTitle:NSLocalizedString(@"Always send crashlog", @"autosend button crashlog dialog")
                                                                 block:^{
                                                                     [prefs setBool:YES forKey:@"sendCrashReports"];\
                                                                     [prefs synchronize];
                                                                     [self sendCrash:crashData];
                                                                 }];

        UIAlertView *alert = [[UIAlertView alloc] initWithTitle:NSLocalizedString(@"App Crashed", @"title crashlog dialog")
                                                        message:NSLocalizedString(@"The App crashed on its last run. Do you want to send the developers a crashlog of the incident to help making the App better? It does not contain any personal data.")
                                                   cancelButton:cancelButton
                                                   otherButtons:@[ send, sendalways ]];
        [alert show];
    }
}

- (NSURL *)buildRequestURLWithIncident:(NSString *)incidentID ofType:(NSString *)type {
    NSString *currentVersion = [[[NSBundle mainBundle] infoDictionary] objectForKey:@"CFBundleVersion"];
    return [NSURL URLWithString:[NSString stringWithFormat:@"%@crashreport.php?app_name=%@&app_version=%@&incident_id=%@&type=%@", HTTP_UPDATE_URL, APP_NAME, currentVersion, incidentID, type]];
}

- (void)sendCrash:(NSData *)data {
    // Fetch incident id
    NSURL *requestURL = [self buildRequestURLWithIncident:@"" ofType:@"getIncidentID"];
    NSData *incidentData = [NSData dataWithContentsOfURL:requestURL];
    NSString *incidentID = nil;

    NSDictionary *response;
    @try {
        NSError *error = nil;
        response = [NSJSONSerialization JSONObjectWithData:incidentData options:0 error:&error];
        if (error) {
            NSLog(@"ERROR: Could not fetch incident ID: %@", error);
            return;
        }
    }
    @catch (NSException *exception) {
        NSLog(@"ERROR: Could not fetch incident ID: %@", exception);
        return;
    }
    if ([response[@"status"] isEqualToString: @"ok"]) {
        incidentID = response[@"incident_id"];
    }

    // Post crash information
    requestURL = [self buildRequestURLWithIncident:incidentID ofType:@"postCrashlog"];
    NSMutableURLRequest *request = [[NSMutableURLRequest alloc] initWithURL:requestURL];
    [request setHTTPMethod:@"POST"];
    [request setHTTPBody:data];

    NSHTTPURLResponse *urlResponse = nil;
    NSError *error = nil;
    NSData *responseData = [NSURLConnection sendSynchronousRequest:request returningResponse:&urlResponse error:&error];
    if (urlResponse.statusCode != 201) {
        NSLog(@"ERROR: Could not upload crash report: %d", urlResponse.statusCode);
    }
    
    @try {
        NSError *error = nil;
        response = [NSJSONSerialization JSONObjectWithData:responseData options:0 error:&error];
        if (error) {
            NSLog(@"ERROR: Could not parse crashreport upload response: %@", error);
            return;
        }
    }
    @catch (NSException *exception) {
        NSLog(@"ERROR: Could not parse crashreport upload response: %@", exception);
        return;
    }
    if ([response[@"status"] isEqualToString: @"ok"]) {
        NSLog(@"Crashreport uploaded successfully");
    }

    UIAlertView *alert = [[UIAlertView alloc] initWithTitle:_t(@"upload complete")
                                                    message:[NSString stringWithFormat:_t(@"crashlog uploaded %@"), incidentID]
                                               cancelButton:[DSTBlockButton buttonWithTitle:_t(@"okay") block:nil] otherButtons:nil];
    [alert show];
    // remove crashreport from queue
    [[PLCrashReporter sharedReporter] purgePendingCrashReport];
}
