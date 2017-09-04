import {Injectable} from '@angular/core';
import {Router} from '@angular/router';
import {HttpClient, HttpErrorResponse, HttpHeaders, HttpParams} from '@angular/common/http';
import {Observable} from 'rxjs/Observable';
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

    constructor (private http: HttpClient, private router: Router) {}

    private initializeBaseHeaders(): void {
        if (!this.baseHeaders) {
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

        return this.http.request(method, `api/${url}`, options)
            .catch((err: HttpErrorResponse) => {
                let message: string;

                if (err.error instanceof Error) {
                    // a client-side or network error occurred
                    message = err.error.message;
                } else {
                    // backend returned an unsuccessful response code

                    if (err.status === 401 && this.router.url !== '/login') {
                        console.log('Unauthorized - redirecting to login', this.router.url);
                        this.router.navigate(['/login']);
                    }

                    try {
                        let errorResp = JSON.parse(err.error);

                        if (errorResp.error) {
                            message = errorResp.error;
                        }
                    } catch (e) {
                    }

                    if (!message) {
                        message = 'Server error';
                    }
                }

                return Observable.throw(message);
            });
    }

    requestData<T>(method: string, url: string, options: RequestOptions = {}) {
        return this.request(method, url, options)
            .map(res => res.data as T);
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
                return Observable.of(null);
            }

            this.currentUser = this.requestData<User>('get','me')
                .publishReplay(1) // cache most recent value
                .refCount(); // keep observable alive as long as there are subscribers
        }

        return this.currentUser;
    }
}
