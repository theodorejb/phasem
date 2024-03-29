import {Injectable} from '@angular/core';
import {Observable, of, map, mergeMap, tap} from 'rxjs';
import {LoginCredentials, LoginResponse, NewUser} from '../models/User';
import {ApiService} from "./ApiService";

@Injectable({
    providedIn: 'root',
})
export class AuthService {
    constructor(private api: ApiService) {}

    createUser(newUser: NewUser) {
        return this.api.requestBody('post', 'auth/user', newUser);
    }

    logIn(credentials: LoginCredentials) {
        return this.api.requestType<LoginResponse>('post', 'auth/token', {body: credentials});
    }

    logOut(): Observable<boolean> {
        return this.api.isLoggedIn()
            .pipe(
                mergeMap(isLoggedIn => {
                    if (isLoggedIn) {
                        return this.api.request('delete', 'auth/token')
                            .pipe(
                                map(() => true),
                            );
                    } else {
                        return of(false);
                    }
                }),
                tap(() => {this.api.unsetCurrentUser();}),
            );
    }
}
