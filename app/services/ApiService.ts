import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {HttpClient, HttpErrorResponse, HttpHeaders, HttpParams} from '@angular/common/http';
import {Observable, of as rxOf, throwError} from 'rxjs';
import {catchError, map, publishReplay, refCount} from 'rxjs/operators';
import * as Cookies from 'es-cookie';
import {User} from '../models/User';

interface RequestOptions {
    body?: any;
    headers?: HttpHeaders;
    observe?: 'body';
    params?: HttpParams;
    responseType?: 'json';
    reportProgress?: boolean;
    withCredentials?: boolean;
}

@Injectable()
export class ApiService {
    private baseHeaders: HttpHeaders;
    private currentUser: Observable<User>;
    private redirectUrl: string | null;
    private currentBuild: string;
    private newBuildAvailable: boolean = false;
    private lastBuildCheck: Date = new Date();

    constructor (private http: HttpClient, private router: Router) {}

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

    request(method: string, url: string, options: RequestOptions = {}) {
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

                        if (err.status === 401) {
                            // invalid auth token
                            this.currentUser = null;

                            if (this.router.url !== '/login') {
                                this.setRedirectUrl(this.router.url);
                                this.router.navigate(['/login']);
                            }
                        }

                        try {
                            let errorResp = JSON.parse(err.error);

                            if (errorResp.error) {
                                message = errorResp.error;
                            }
                        } catch (e) {
                            // do nothing
                        }

                        if (!message) {
                            message = 'Server error';
                        }
                    }

                    return throwError(message);
                }),
            );
    }

    requestData<T>(method: string, url: string, options: RequestOptions = {}) {
        return this.request(method, url, options)
            .pipe(
                map((res: {data: any}) => res.data as T),
            );
    }

    requestBody(method: string, url: string, object: {[key: string]: any}) {
        return this.request(method, url, {body: object});
    }

    setAuth(token: string): void {
        this.currentUser = null;
        Cookies.set('ApiAuth', token, {expires: 30});
        this.baseHeaders = null;
    }

    unsetCurrentUser(): void {
        this.currentUser = null;
        Cookies.remove('ApiAuth');
        this.baseHeaders = null;
    }

    getCurrentUser(): Observable<User | null> {
        if (!this.currentUser) {
            // avoid multiple network requests if getCurrentUser() is called multiple times

            if (!this.getAuthHeader()) {
                return rxOf(null);
            }

            this.currentUser = this.requestData<User>('get','me')
                .pipe(
                    publishReplay(1), // cache most recent value
                    refCount(), // keep observable alive as long as there are subscribers
                );
        }

        return this.currentUser;
    }

    isLoggedIn(): Observable<boolean> {
        return this.getCurrentUser()
            .pipe(
                map(user => user !== null),
            );
    }

    setRedirectUrl(url: string | null): void {
        this.redirectUrl = url;
    }

    getRedirectUrl(): string | null {
        return this.redirectUrl;
    }

    isNewBuildAvailable(): boolean {
        return this.newBuildAvailable;
    }
}

function getBuildHash(doc: Document): string {
    let scripts = doc.getElementsByTagName('script');

    for (let i = 0; i < scripts.length; i++) {
        if (scripts.item(i).src) {
            return scripts.item(i).src.split('?')[1];
        }
    }
}
