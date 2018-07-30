import {Injectable} from '@angular/core';
import {Observable, of as rxOf} from 'rxjs';
import {map, mergeMap, tap} from 'rxjs/operators';
import {LoginCredentials, NewUser} from '../models/User';
import {ApiService} from "./ApiService";

@Injectable({
    providedIn: 'root',
})
export class AuthService {
    constructor (private api: ApiService) {}

    createUser(newUser: NewUser) {
        return this.api.requestBody('post', 'auth/user', newUser);
    }

    logIn(credentials: LoginCredentials) {
        return this.api.requestBody('post', 'auth/token', credentials)
            .pipe(
                map((resp: {token: string}) => {this.api.setAuth(resp.token);}),
            );
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
                        return rxOf(false);
                    }
                }),
                tap(() => {this.api.unsetCurrentUser();}),
            );
    }
}
