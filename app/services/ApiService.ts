import {HttpClient, HttpErrorResponse, HttpHeaders} from '@angular/common/http';
import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import * as Cookies from 'es-cookie';
import {Observable, of as rxOf, throwError} from 'rxjs';
import {catchError, map, publishReplay, refCount, tap} from 'rxjs/operators';
import {User} from '../models/User';

interface RequestOptions {
    body?: any;
    headers?: HttpHeaders;
    observe?: 'body' | 'events' | 'response';
    params?: {[param: string]: string | string[]};
    responseType?: 'json';
    reportProgress?: boolean;
    withCredentials?: boolean;
}

@Injectable({
    providedIn: 'root',
})
export class ApiService {
    private baseHeaders: HttpHeaders;
    public currentUser: User | null = null;
    private currentUserObs: Observable<User>;
    private redirectUrl: string | null;
    private currentBuild: string;
    private newBuildAvailable: boolean = false;
    private lastBuildCheck: Date = new Date();

    constructor(private http: HttpClient, private router: Router) {}

    private initializeBaseHeaders(): void {
        if (!this.baseHeaders) {
            this.currentBuild = getBuildHash(document);
            this.baseHeaders = new HttpHeaders();
            let token = Cookies.get('ApiAuth');

            if (token) {
                this.baseHeaders = this.baseHeaders.set('Authorization', `Bearer ${token}`);
            }
        }
    }

    getAuthHeader(): string | null {
        this.initializeBaseHeaders();
        return this.baseHeaders.get('Authorization');
    }

    request(method: string, url: string, options: RequestOptions = {}, redirectOnError = true) {
        this.initializeBaseHeaders();

        if (!options.headers) {
            options.headers = new HttpHeaders();
        }

        if (!options.headers.has('Authorization') && this.baseHeaders.has('Authorization')) {
            options.headers = options.headers.set('Authorization', this.baseHeaders.get('Authorization'));
        }

        if (!this.newBuildAvailable) {
            let thresholdDate = new Date();
            thresholdDate.setMinutes(thresholdDate.getMinutes() - 2);

            if (thresholdDate > this.lastBuildCheck) {
                this.lastBuildCheck = new Date();

                setTimeout(() => {
                    this.http.get('login', {responseType: 'text'}).subscribe(
                        page => {
                            let build = getBuildHash(new DOMParser().parseFromString(page, "text/html"));

                            if (build !== this.currentBuild) {
                                this.newBuildAvailable = true;
                            }
                        },
                        error => {
                            console.error('Failed to request index page');
                        },
                    );
                }, 3000);
            }
        }

        return this.http.request(method, `api/${url}`, options)
            .pipe(
                catchError((err: HttpErrorResponse) => {
                    let message: string;

                    if (err.error instanceof Error) {
                        // a client-side or network error occurred
                        message = err.error.message;
                    } else {
                        // backend returned an unsuccessful response code
                        let errorResp = err.error;

                        if (errorResp.error) {
                            message = errorResp.error;
                        }

                        if (err.status === 401) {
                            let authUrl = '/login';

                            if (message === 'Two-factor authentication code required') {
                                authUrl = '/confirm-mfa';
                            } else {
                                // invalid auth token
                                this.unsetCurrentUser();
                            }

                            if (redirectOnError && this.router.url !== authUrl) {
                                this.setRedirectUrl(this.router.url);
                                this.router.navigate([authUrl]);
                            }
                        }

                        if (!message) {
                            message = 'Server error';
                        }
                    }

                    return throwError(message);
                }),
            );
    }

    requestData<T>(method: string, url: string, options: RequestOptions = {}, redirectOnError = true) {
        return this.request(method, url, options, redirectOnError)
            .pipe(
                map((res: {data: any}) => res.data as T),
            );
    }

    requestType<T>(method: string, url: string, options: RequestOptions = {}) {
        return this.request(method, url, options) as Observable<T>;
    }

    requestBody(method: string, url: string, object: {[key: string]: any}) {
        return this.request(method, url, {body: object});
    }

    setAuth(token: string, clearUser: boolean): void {
        if (clearUser) {
            this.currentUser = this.currentUserObs = null;
        }

        Cookies.set('ApiAuth', token, {expires: 91});
        this.baseHeaders = null;
    }

    unsetCurrentUser(): void {
        this.currentUser = this.currentUserObs = null;
        Cookies.remove('ApiAuth');
        this.baseHeaders = null;
    }

    getCurrentUser(redirectOnError = true): Observable<User | null> {
        if (this.currentUser) {
            return rxOf(this.currentUser);
        }

        if (!this.currentUserObs) {
            // avoid multiple network requests if getCurrentUser() is called multiple times

            if (!this.getAuthHeader()) {
                return rxOf(null);
            }

            this.currentUserObs = this.requestData<User>('get','me', {}, redirectOnError)
                .pipe(
                    tap(user => {this.currentUser = user;}),
                    publishReplay(1), // cache most recent value
                    refCount(), // keep observable alive as long as there are subscribers
                );
        }

        return this.currentUserObs;
    }

    isLoggedIn(): Observable<boolean> {
        return this.getCurrentUser(false)
            .pipe(
                catchError(() => rxOf(null)),
                map(user => user !== null),
            );
    }

    setRedirectUrl(url: string | null): void {
        this.redirectUrl = url;
    }

    defaultRedirect() {
        let redirectUrl = this.redirectUrl || '/settings';
        this.redirectUrl = null;
        this.router.navigate([redirectUrl]);
    }

    isNewBuildAvailable(): boolean {
        return this.newBuildAvailable;
    }
}

function getBuildHash(doc: Document): string {
    // concatenate all the file names/hashes and prompt to reload if any of them change
    let scripts = doc.getElementsByTagName('script');
    let hash = '';

    for (let i = 0; i < scripts.length; i++) {
        if (scripts.item(i).src) {
            hash += scripts.item(i).src.split('/').pop() + ',';
        }
    }

    return hash;
}
